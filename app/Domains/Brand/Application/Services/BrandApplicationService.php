<?php

namespace App\Domains\Brand\Application\Services;

use App\Domains\Brand\Application\DTOs\SaveAudienceData;
use App\Domains\Brand\Application\DTOs\SaveBrandProfileData;
use App\Domains\Brand\Application\DTOs\SaveBrandVoiceData;
use App\Domains\Brand\Application\DTOs\SaveCompetitorData;
use App\Domains\Brand\Application\DTOs\SaveGoalData;
use App\Domains\Brand\Application\DTOs\SaveProductServiceData;
use App\Domains\Brand\Domain\DTOs\BrandContext;
use App\Domains\Brand\Domain\Repositories\BrandAudienceRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandCompetitorRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandGoalRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandProductServiceRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandProfileRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandVoiceRepositoryInterface;
use App\Domains\Brand\Domain\Services\BrandCompletenessService;
use App\Domains\Brand\Domain\Services\BrandContextBuilder;
use App\Domains\Brand\Domain\Repositories\BrandAssetRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandAssetModel;
use App\Domains\Billing\Domain\Services\EntitlementService;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BrandApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService,
        private readonly BrandCompletenessService $completenessService,
        private readonly BrandContextBuilder $contextBuilder,
        private readonly EntitlementService $entitlementService,
        private readonly BrandProfileRepositoryInterface $profileRepository,
        private readonly BrandProductServiceRepositoryInterface $productRepository,
        private readonly BrandAudienceRepositoryInterface $audienceRepository,
        private readonly BrandVoiceRepositoryInterface $voiceRepository,
        private readonly BrandGoalRepositoryInterface $goalRepository,
        private readonly BrandCompetitorRepositoryInterface $competitorRepository,
        private readonly BrandAssetRepositoryInterface $assetRepository
    ) {}

    /**
     * List all Brand Profiles belonging to the organization.
     */
    public function listBrands(TenantContext $context): Collection
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');

        return $this->profileRepository->listByOrganizationId($context->organizationId);
    }

    /**
     * Get Brand Profile with completeness score for the current tenant.
     */
    public function getBrandBrain(TenantContext $context, ?int $brandProfileId = null): array
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');

        $targetBrandId = $brandProfileId ?? $context->brandId;
        $profile = $this->profileRepository->findWithRelationsByOrganizationId($context->organizationId, $targetBrandId);
        $completeness = $this->completenessService->calculate($profile);

        return [
            'profile' => $profile,
            'completeness' => $completeness,
        ];
    }

    /**
     * Create or update the Brand Profile using typed DTO with quota verification.
     */
    public function saveBrandProfile(TenantContext $context, SaveBrandProfileData $data, ?int $brandProfileId = null): object
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $targetId = $brandProfileId ?: $data->id;

        $existing = null;
        if ($targetId) {
            $existing = BrandProfileModel::where('organization_id', $context->organizationId)->where('id', $targetId)->first();
        } else {
            $existing = BrandProfileModel::where('organization_id', $context->organizationId)->where('business_name', $data->businessName)->first();
        }

        // If creating a brand that doesn't exist yet, verify subscription entitlement limit
        if (!$existing) {
            $this->entitlementService->assertCanCreateBrand($context->organizationId);
        }

        $profile = $this->profileRepository->saveForOrganization($context->organizationId, $data, $targetId ?: $existing?->id);

        $this->auditService->log(
            action: $existing ? 'brand.profile_updated' : 'brand.profile_created',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_profile',
            entityId: (string) $profile->id
        );

        return $profile;
    }

    /**
     * Delete a brand profile.
     */
    public function deleteBrand(TenantContext $context, int $brandProfileId): void
    {
        TenantIsolationGuard::assertPermission($context, 'brand.delete');

        $brand = BrandProfileModel::where('organization_id', $context->organizationId)
            ->where('id', $brandProfileId)
            ->firstOrFail();

        $brand->delete();

        $this->auditService->log(
            action: 'brand.profile_deleted',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_profile',
            entityId: (string) $brandProfileId
        );
    }

    /**
     * List products/services for tenant.
     */
    public function listProducts(TenantContext $context): Collection
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');
        return $this->productRepository->listByOrganizationId($context->organizationId);
    }

    /**
     * Add or update product/service.
     */
    public function saveProductService(TenantContext $context, SaveProductServiceData $data, ?int $productId = null): object
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = $this->profileRepository->ensureExistsForOrganization($context->organizationId);

        if ($productId) {
            $existing = $this->productRepository->findByIdForOrganization($context->organizationId, $productId);
            if (!$existing) {
                throw new NotFoundHttpException('Product not found.');
            }
            $model = $this->productRepository->updateForOrganization($context->organizationId, $productId, $data);
        } else {
            $model = $this->productRepository->createForOrganization($context->organizationId, $profile->id, $data);
        }

        $this->auditService->log(
            action: $productId ? 'brand.product_updated' : 'brand.product_created',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_product_service',
            entityId: (string) $model->id
        );

        return $model;
    }

    /**
     * Delete product/service.
     */
    public function deleteProductService(TenantContext $context, int $productId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $existing = $this->productRepository->findByIdForOrganization($context->organizationId, $productId);
        if (!$existing) {
            throw new NotFoundHttpException('Product not found.');
        }

        $this->productRepository->deleteForOrganization($context->organizationId, $productId);

        $this->auditService->log(
            action: 'brand.product_deleted',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_product_service',
            entityId: (string) $productId
        );

        return true;
    }

    /**
     * List audiences for tenant.
     */
    public function listAudiences(TenantContext $context): Collection
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');
        return $this->audienceRepository->listByOrganizationId($context->organizationId);
    }

    /**
     * Add or update Target Audience.
     */
    public function saveAudience(TenantContext $context, SaveAudienceData $data, ?int $audienceId = null): object
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = $this->profileRepository->ensureExistsForOrganization($context->organizationId);

        if ($audienceId) {
            $existing = $this->audienceRepository->findByIdForOrganization($context->organizationId, $audienceId);
            if (!$existing) {
                throw new NotFoundHttpException('Audience profile not found.');
            }
            $model = $this->audienceRepository->updateForOrganization($context->organizationId, $audienceId, $data);
        } else {
            $model = $this->audienceRepository->createForOrganization($context->organizationId, $profile->id, $data);
        }

        $this->auditService->log(
            action: $audienceId ? 'brand.audience_updated' : 'brand.audience_created',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_audience',
            entityId: (string) $model->id
        );

        return $model;
    }

    /**
     * Delete Target Audience.
     */
    public function deleteAudience(TenantContext $context, int $audienceId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $existing = $this->audienceRepository->findByIdForOrganization($context->organizationId, $audienceId);
        if (!$existing) {
            throw new NotFoundHttpException('Audience profile not found.');
        }

        $this->audienceRepository->deleteForOrganization($context->organizationId, $audienceId);

        $this->auditService->log(
            action: 'brand.audience_deleted',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_audience',
            entityId: (string) $audienceId
        );

        return true;
    }

    /**
     * Save Brand Voice & Tone settings.
     */
    public function saveBrandVoice(TenantContext $context, SaveBrandVoiceData $data): object
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = $this->profileRepository->ensureExistsForOrganization($context->organizationId);
        $voice = $this->voiceRepository->saveForOrganization($context->organizationId, $profile->id, $data);

        $this->auditService->log(
            action: 'brand.voice_updated',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_voice',
            entityId: (string) $voice->id
        );

        return $voice;
    }

    /**
     * List goals for tenant.
     */
    public function listGoals(TenantContext $context): Collection
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');
        return $this->goalRepository->listByOrganizationId($context->organizationId);
    }

    /**
     * Add or update Business Goal.
     */
    public function saveGoal(TenantContext $context, SaveGoalData $data, ?int $goalId = null): object
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = $this->profileRepository->ensureExistsForOrganization($context->organizationId);

        if ($goalId) {
            $existing = $this->goalRepository->findByIdForOrganization($context->organizationId, $goalId);
            if (!$existing) {
                throw new NotFoundHttpException('Goal not found.');
            }
            $model = $this->goalRepository->updateForOrganization($context->organizationId, $goalId, $data);
        } else {
            $model = $this->goalRepository->createForOrganization($context->organizationId, $profile->id, $data);
        }

        $this->auditService->log(
            action: $goalId ? 'brand.goal_updated' : 'brand.goal_created',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_goal',
            entityId: (string) $model->id
        );

        return $model;
    }

    /**
     * Delete Business Goal.
     */
    public function deleteGoal(TenantContext $context, int $goalId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $existing = $this->goalRepository->findByIdForOrganization($context->organizationId, $goalId);
        if (!$existing) {
            throw new NotFoundHttpException('Goal not found.');
        }

        $this->goalRepository->deleteForOrganization($context->organizationId, $goalId);

        $this->auditService->log(
            action: 'brand.goal_deleted',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_goal',
            entityId: (string) $goalId
        );

        return true;
    }

    /**
     * List competitors for tenant.
     */
    public function listCompetitors(TenantContext $context): Collection
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');
        return $this->competitorRepository->listByOrganizationId($context->organizationId);
    }

    /**
     * Add or update Competitor.
     */
    public function saveCompetitor(TenantContext $context, SaveCompetitorData $data, ?int $competitorId = null): object
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = $this->profileRepository->ensureExistsForOrganization($context->organizationId);

        if ($competitorId) {
            $existing = $this->competitorRepository->findByIdForOrganization($context->organizationId, $competitorId);
            if (!$existing) {
                throw new NotFoundHttpException('Competitor not found.');
            }
            $model = $this->competitorRepository->updateForOrganization($context->organizationId, $competitorId, $data);
        } else {
            $model = $this->competitorRepository->createForOrganization($context->organizationId, $profile->id, $data);
        }

        $this->auditService->log(
            action: $competitorId ? 'brand.competitor_updated' : 'brand.competitor_created',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_competitor',
            entityId: (string) $model->id
        );

        return $model;
    }

    /**
     * Delete Competitor.
     */
    public function deleteCompetitor(TenantContext $context, int $competitorId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $existing = $this->competitorRepository->findByIdForOrganization($context->organizationId, $competitorId);
        if (!$existing) {
            throw new NotFoundHttpException('Competitor not found.');
        }

        $this->competitorRepository->deleteForOrganization($context->organizationId, $competitorId);

        $this->auditService->log(
            action: 'brand.competitor_deleted',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_competitor',
            entityId: (string) $competitorId
        );

        return true;
    }

    /**
     * Generate sanitized AI Brand Context for the current tenant.
     */
    public function getAIBrandContext(TenantContext $context, ?int $audienceId = null, ?int $productId = null): BrandContext
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');

        // Verify audience belongs to current tenant if specified
        if ($audienceId) {
            $audience = $this->audienceRepository->findByIdForOrganization($context->organizationId, $audienceId);
            if (!$audience) {
                $audienceId = null; // Ignore cross-tenant audience
            }
        }

        // Verify product belongs to current tenant if specified
        if ($productId) {
            $product = $this->productRepository->findByIdForOrganization($context->organizationId, $productId);
            if (!$product) {
                $productId = null; // Ignore cross-tenant product
            }
        }

        return $this->contextBuilder->build($context->organizationId, $context->brandId);
    }

    /**
     * List Brand Assets for the tenant.
     */
    public function listBrandAssets(TenantContext $context, ?string $type = null): Collection
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');

        $assets = $this->assetRepository->listByOrganizationId($context->organizationId, $type);

        return $assets->map(function (BrandAssetModel $asset) {
            $asset->public_url = $asset->file_path ? Storage::disk('public')->url($asset->file_path) : null;
            return $asset;
        });
    }

    /**
     * Upload and store Brand Asset.
     */
    public function uploadBrandAsset(
        TenantContext $context,
        UploadedFile $file,
        string $type = 'logo',
        ?string $name = null
    ): BrandAssetModel {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = $this->profileRepository->ensureExistsForOrganization($context->organizationId);

        $filename = ($name ? \Illuminate\Support\Str::slug($name) : $type) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs("brand-assets/{$context->organizationId}", $filename, 'public');

        $asset = $this->assetRepository->createForOrganization($context->organizationId, $profile->id, [
            'name' => $name ?: $file->getClientOriginalName(),
            'type' => $type,
            'file_path' => $storedPath,
            'mime_type' => $file->getClientMimeType() ?: 'image/png',
            'file_size' => $file->getSize(),
            'is_public' => true,
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
            ],
        ]);

        $asset->public_url = Storage::disk('public')->url($storedPath);

        $this->auditService->log(
            action: 'brand.asset_uploaded',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_asset',
            entityId: (string) $asset->id,
            metadata: ['type' => $type, 'name' => $asset->name]
        );

        return $asset;
    }

    /**
     * Delete Brand Asset.
     */
    public function deleteBrandAsset(TenantContext $context, int $assetId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $existing = $this->assetRepository->findByIdForOrganization($context->organizationId, $assetId);
        if (!$existing) {
            throw new NotFoundHttpException('Brand asset not found.');
        }

        $this->assetRepository->deleteForOrganization($context->organizationId, $assetId);

        $this->auditService->log(
            action: 'brand.asset_deleted',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_asset',
            entityId: (string) $assetId
        );

        return true;
    }
}
