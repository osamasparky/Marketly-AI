<?php

namespace App\Domains\Creative\Application\Services;

use App\Domains\Billing\Domain\Services\EntitlementService;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Creative\Domain\Services\ReelScriptGeneratorAgent;
use App\Domains\Creative\Domain\Services\VisualAssetGeneratorAgent;
use App\Domains\Creative\Domain\Services\VisualPromptBuilder;
use App\Domains\Creative\Infrastructure\Persistence\Models\MediaAssetModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreativeApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService,
        private readonly EntitlementService $entitlementService,
        private readonly VisualPromptBuilder $promptBuilder,
        private readonly VisualAssetGeneratorAgent $visualGenerator,
        private readonly ReelScriptGeneratorAgent $reelGenerator
    ) {}

    /**
     * List media assets for the active tenant.
     */
    public function getAssets(TenantContext $context, array $filters = []): Collection
    {
        TenantIsolationGuard::assertPermission($context, 'creative.view');

        $query = MediaAssetModel::with(['post', 'author'])
            ->where('organization_id', $context->organizationId);

        if (!empty($filters['aspect_ratio'])) {
            $query->where('aspect_ratio', $filters['aspect_ratio']);
        }

        if (!empty($filters['file_type'])) {
            $query->where('file_type', $filters['file_type']);
        }

        if (!empty($filters['content_post_id'])) {
            $query->where('content_post_id', (int) $filters['content_post_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('text_overlay', 'LIKE', "%{$search}%")
                  ->orWhere('visual_style', 'LIKE', "%{$search}%");
            });
        }

        return $query->latest()->get();
    }

    /**
     * Get a specific media asset.
     */
    public function getAsset(TenantContext $context, int $assetId): MediaAssetModel
    {
        TenantIsolationGuard::assertPermission($context, 'creative.view');

        return MediaAssetModel::with(['post', 'author'])
            ->where('organization_id', $context->organizationId)
            ->where('id', $assetId)
            ->firstOrFail();
    }

    /**
     * Generate visual graphic asset.
     */
    public function generateVisual(TenantContext $context, array $params): MediaAssetModel
    {
        TenantIsolationGuard::assertPermission($context, 'creative.create');

        // Quota assertion & consumption
        $this->entitlementService->assertCanAndConsume($context->organizationId, 'ai_content', 1);

        $postId = isset($params['content_post_id']) ? (int) $params['content_post_id'] : null;
        $title = $params['title'] ?? null;
        $hook = $params['hook'] ?? null;

        // If grounded in a content post, pull title & hook from post
        if ($postId) {
            $post = ContentPostModel::where('organization_id', $context->organizationId)
                ->where('id', $postId)
                ->firstOrFail();

            $title = $title ?: $post->title;
            $hook = $hook ?: ($post->hook ?: mb_substr($post->caption, 0, 100));
        }

        $visualParams = $this->promptBuilder->build(
            organizationId: $context->organizationId,
            title: $title,
            hook: $hook,
            visualStyle: $params['visual_style'] ?? 'branded_quote',
            aspectRatio: $params['aspect_ratio'] ?? '1:1',
            customPalette: $params['color_palette'] ?? null
        );

        $generated = $this->visualGenerator->generate($visualParams);

        return DB::transaction(function () use ($context, $postId, $visualParams, $generated) {
            $asset = MediaAssetModel::create([
                'organization_id' => $context->organizationId,
                'content_post_id' => $postId,
                'created_by' => $context->userId,
                'title' => $visualParams['title'],
                'file_name' => $generated['file_name'],
                'file_type' => $generated['file_type'],
                'mime_type' => $generated['mime_type'],
                'file_size_bytes' => $generated['file_size_bytes'],
                'width' => $generated['width'],
                'height' => $generated['height'],
                'aspect_ratio' => $generated['aspect_ratio'],
                'prompt_used' => $generated['prompt_used'],
                'visual_style' => $generated['visual_style'],
                'text_overlay' => $generated['text_overlay'],
                'color_palette' => $generated['color_palette'],
                'metadata' => $generated['metadata'],
                'status' => 'ready',
            ]);

            $this->auditService->log(
                action: 'creative.generated',
                organizationId: $context->organizationId,
                userId: $context->userId,
                entityType: 'media_asset',
                entityId: (string) $asset->id
            );

            return $asset->load(['post', 'author']);
        });
    }

    /**
     * Generate video reel script asset.
     */
    public function generateReelScript(TenantContext $context, array $params): MediaAssetModel
    {
        TenantIsolationGuard::assertPermission($context, 'creative.create');

        $this->entitlementService->assertCanAndConsume($context->organizationId, 'ai_content', 1);

        $postId = isset($params['content_post_id']) ? (int) $params['content_post_id'] : null;
        $title = $params['title'] ?? 'Strategic Growth Framework';
        $hook = $params['hook'] ?? 'Stop doing manual marketing in 2026.';
        $dialect = $params['dialect'] ?? 'saudi';

        if ($postId) {
            $post = ContentPostModel::where('organization_id', $context->organizationId)
                ->where('id', $postId)
                ->firstOrFail();

            $title = $post->title;
            $hook = $post->hook ?: mb_substr($post->caption, 0, 100);
            $dialect = $post->dialect ?: 'saudi';
        }

        $scriptData = $this->reelGenerator->generate([
            'business_name' => 'Marketly AI',
            'title' => $title,
            'hook' => $hook,
            'dialect' => $dialect,
        ], $params['prompt'] ?? null);

        return DB::transaction(function () use ($context, $postId, $title, $hook, $scriptData) {
            $asset = MediaAssetModel::create([
                'organization_id' => $context->organizationId,
                'content_post_id' => $postId,
                'created_by' => $context->userId,
                'title' => $scriptData['title'],
                'file_name' => 'reel_script_' . uniqid() . '.json',
                'file_type' => 'video_script',
                'mime_type' => 'application/json',
                'file_size_bytes' => strlen(json_encode($scriptData)),
                'width' => 1080,
                'height' => 1920,
                'aspect_ratio' => '9:16',
                'visual_style' => 'video_reel',
                'text_overlay' => $hook,
                'metadata' => $scriptData,
                'status' => 'ready',
            ]);

            $this->auditService->log(
                action: 'creative.reel_script_generated',
                organizationId: $context->organizationId,
                userId: $context->userId,
                entityType: 'media_asset',
                entityId: (string) $asset->id
            );

            return $asset->load(['post', 'author']);
        });
    }

    /**
     * Attach a media asset to a content post.
     */
    public function attachToPost(TenantContext $context, int $assetId, int $postId): MediaAssetModel
    {
        TenantIsolationGuard::assertPermission($context, 'creative.create');

        $asset = $this->getAsset($context, $assetId);

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $asset->update(['content_post_id' => $post->id]);

        return $asset->fresh(['post', 'author']);
    }

    /**
     * Delete a media asset.
     */
    public function deleteAsset(TenantContext $context, int $assetId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'creative.delete');

        $asset = $this->getAsset($context, $assetId);
        $asset->delete();

        $this->auditService->log(
            action: 'creative.deleted',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'media_asset',
            entityId: (string) $assetId
        );

        return true;
    }
}
