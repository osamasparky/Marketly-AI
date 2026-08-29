<?php

namespace App\Domains\Brand\Application\Services;

use App\Domains\Brand\Domain\DTOs\BrandContext;
use App\Domains\Brand\Domain\Services\BrandCompletenessService;
use App\Domains\Brand\Domain\Services\BrandContextBuilder;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandAssetModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandAudienceModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandCompetitorModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandGoalModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandLocationModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProductServiceModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use InvalidArgumentException;

class BrandApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService,
        private readonly BrandCompletenessService $completenessService,
        private readonly BrandContextBuilder $contextBuilder
    ) {}

    /**
     * Get or initialize Brand Profile with completeness score for the current tenant.
     */
    public function getBrandBrain(TenantContext $context): array
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');

        $profile = BrandProfileModel::with([
            'productsServices',
            'audiences',
            'voice',
            'goals',
            'competitors',
            'locations',
            'assets',
        ])->where('organization_id', $context->organizationId)->first();

        $completeness = $this->completenessService->calculate($profile);

        return [
            'profile' => $profile,
            'completeness' => $completeness,
        ];
    }

    /**
     * Create or update the Brand Profile.
     */
    public function saveBrandProfile(TenantContext $context, array $data): BrandProfileModel
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $existing = BrandProfileModel::where('organization_id', $context->organizationId)->first();
        $nextVersion = $existing ? ($existing->version + 1) : 1;

        $profile = BrandProfileModel::updateOrCreate(
            ['organization_id' => $context->organizationId],
            [
                'business_name' => trim($data['business_name'] ?? ($existing?->business_name ?? 'My Business')),
                'legal_name' => $data['legal_name'] ?? $existing?->legal_name,
                'industry' => $data['industry'] ?? ($existing?->industry ?? 'Technology'),
                'business_type' => $data['business_type'] ?? ($existing?->business_type ?? 'B2B'),
                'description' => $data['description'] ?? $existing?->description,
                'website' => $data['website'] ?? $existing?->website,
                'phone' => $data['phone'] ?? $existing?->phone,
                'email' => $data['email'] ?? $existing?->email,
                'country' => $data['country'] ?? ($existing?->country ?? 'SA'),
                'region' => $data['region'] ?? $existing?->region,
                'city' => $data['city'] ?? $existing?->city,
                'timezone' => $data['timezone'] ?? ($existing?->timezone ?? 'Asia/Riyadh'),
                'default_locale' => $data['default_locale'] ?? ($existing?->default_locale ?? 'ar'),
                'tagline' => $data['tagline'] ?? $existing?->tagline,
                'mission' => $data['mission'] ?? $existing?->mission,
                'vision' => $data['vision'] ?? $existing?->vision,
                'values' => $data['values'] ?? ($existing?->values ?? []),
                'positioning' => $data['positioning'] ?? $existing?->positioning,
                'unique_selling_points' => $data['unique_selling_points'] ?? ($existing?->unique_selling_points ?? []),
                'brand_promise' => $data['brand_promise'] ?? $existing?->brand_promise,
                'version' => $nextVersion,
            ]
        );

        $this->auditService->log(
            action: 'brand.profile_updated',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'brand_profile',
            entityId: (string) $profile->id
        );

        return $profile;
    }

    /**
     * Add or update product/service.
     */
    public function saveProductService(TenantContext $context, array $data, ?int $productId = null): BrandProductServiceModel
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = BrandProfileModel::firstOrCreate(
            ['organization_id' => $context->organizationId],
            ['business_name' => 'My Business']
        );

        if ($productId) {
            $model = BrandProductServiceModel::where('organization_id', $context->organizationId)
                ->where('id', $productId)
                ->firstOrFail();
            $model->update($data);
        } else {
            $data['organization_id'] = $context->organizationId;
            $data['brand_profile_id'] = $profile->id;
            $model = BrandProductServiceModel::create($data);
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

        $model = BrandProductServiceModel::where('organization_id', $context->organizationId)
            ->where('id', $productId)
            ->firstOrFail();

        $model->delete();

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
     * Add or update Target Audience.
     */
    public function saveAudience(TenantContext $context, array $data, ?int $audienceId = null): BrandAudienceModel
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = BrandProfileModel::firstOrCreate(
            ['organization_id' => $context->organizationId],
            ['business_name' => 'My Business']
        );

        if ($audienceId) {
            $model = BrandAudienceModel::where('organization_id', $context->organizationId)
                ->where('id', $audienceId)
                ->firstOrFail();
            $model->update($data);
        } else {
            $data['organization_id'] = $context->organizationId;
            $data['brand_profile_id'] = $profile->id;
            $model = BrandAudienceModel::create($data);
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

        $model = BrandAudienceModel::where('organization_id', $context->organizationId)
            ->where('id', $audienceId)
            ->firstOrFail();

        $model->delete();

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
    public function saveBrandVoice(TenantContext $context, array $data): BrandVoiceModel
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = BrandProfileModel::firstOrCreate(
            ['organization_id' => $context->organizationId],
            ['business_name' => 'My Business']
        );

        $voice = BrandVoiceModel::updateOrCreate(
            ['organization_id' => $context->organizationId],
            [
                'brand_profile_id' => $profile->id,
                'primary_tones' => $data['primary_tones'] ?? ['professional'],
                'formality_scale' => $data['formality_scale'] ?? 3,
                'playfulness_scale' => $data['playfulness_scale'] ?? 2,
                'boldness_scale' => $data['boldness_scale'] ?? 3,
                'simplicity_scale' => $data['simplicity_scale'] ?? 4,
                'preferred_phrases' => $data['preferred_phrases'] ?? [],
                'forbidden_phrases' => $data['forbidden_phrases'] ?? [],
                'words_to_avoid' => $data['words_to_avoid'] ?? [],
                'words_to_emphasize' => $data['words_to_emphasize'] ?? [],
                'cta_preferences' => $data['cta_preferences'] ?? [],
                'emoji_style' => $data['emoji_style'] ?? 'moderate',
                'hashtag_style' => $data['hashtag_style'] ?? 'targeted',
                'dialect' => $data['dialect'] ?? 'saudi',
                'language_specific_notes' => $data['language_specific_notes'] ?? null,
            ]
        );

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
     * Add or update Business Goal.
     */
    public function saveGoal(TenantContext $context, array $data, ?int $goalId = null): BrandGoalModel
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = BrandProfileModel::firstOrCreate(
            ['organization_id' => $context->organizationId],
            ['business_name' => 'My Business']
        );

        if ($goalId) {
            $model = BrandGoalModel::where('organization_id', $context->organizationId)
                ->where('id', $goalId)
                ->firstOrFail();
            $model->update($data);
        } else {
            $data['organization_id'] = $context->organizationId;
            $data['brand_profile_id'] = $profile->id;
            $model = BrandGoalModel::create($data);
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

        $model = BrandGoalModel::where('organization_id', $context->organizationId)
            ->where('id', $goalId)
            ->firstOrFail();

        $model->delete();

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
     * Add or update Competitor.
     */
    public function saveCompetitor(TenantContext $context, array $data, ?int $competitorId = null): BrandCompetitorModel
    {
        TenantIsolationGuard::assertPermission($context, 'brand.update');

        $profile = BrandProfileModel::firstOrCreate(
            ['organization_id' => $context->organizationId],
            ['business_name' => 'My Business']
        );

        if ($competitorId) {
            $model = BrandCompetitorModel::where('organization_id', $context->organizationId)
                ->where('id', $competitorId)
                ->firstOrFail();
            $model->update($data);
        } else {
            $data['organization_id'] = $context->organizationId;
            $data['brand_profile_id'] = $profile->id;
            $model = BrandCompetitorModel::create($data);
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

        $model = BrandCompetitorModel::where('organization_id', $context->organizationId)
            ->where('id', $competitorId)
            ->firstOrFail();

        $model->delete();

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
    public function getAIBrandContext(TenantContext $context): BrandContext
    {
        TenantIsolationGuard::assertPermission($context, 'brand.view');
        return $this->contextBuilder->build($context->organizationId);
    }
}
