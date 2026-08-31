<?php

namespace App\Domains\Content\Application\Services;

use App\Domains\Billing\Domain\Services\EntitlementService;
use App\Domains\Content\Domain\Services\ContentContextBuilder;
use App\Domains\Content\Domain\Services\ContentGeneratorAgent;
use App\Domains\Content\Domain\Services\ContentQualityAgent;
use App\Domains\Content\Domain\Services\PlatformRepurposingService;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentQualityAuditModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentVariationModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ContentApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService,
        private readonly EntitlementService $entitlementService,
        private readonly ContentContextBuilder $contextBuilder,
        private readonly ContentGeneratorAgent $generatorAgent,
        private readonly PlatformRepurposingService $repurposingService,
        private readonly ContentQualityAgent $qualityAgent
    ) {}

    /**
     * List posts for the active tenant organization with optional filtering.
     */
    public function getPosts(TenantContext $context, array $filters = []): Collection|LengthAwarePaginator
    {
        TenantIsolationGuard::assertPermission($context, 'content.view');

        $query = ContentPostModel::with([
            'variations',
            'latestAudit',
            'pillar',
            'campaignTheme',
            'author',
        ])->where('organization_id', $context->organizationId);

        if ($context->brandId) {
            $query->where('brand_profile_id', $context->brandId);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['primary_platform'])) {
            $query->where('primary_platform', $filters['primary_platform']);
        }

        if (!empty($filters['pillar_id'])) {
            $query->where('pillar_id', (int) $filters['pillar_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('caption', 'LIKE', "%{$search}%")
                  ->orWhere('hook', 'LIKE', "%{$search}%");
            });
        }

        return $query->latest()->get();
    }

    /**
     * Get a specific content post with all variations and audit history.
     */
    public function getPost(TenantContext $context, int $postId): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.view');

        $query = ContentPostModel::with([
            'variations',
            'qualityAudits',
            'latestAudit',
            'pillar',
            'campaignTheme',
            'brandProfile',
            'author',
        ])->where('organization_id', $context->organizationId)
          ->where('id', $postId);

        if ($context->brandId) {
            $query->where('brand_profile_id', $context->brandId);
        }

        return $query->firstOrFail();
    }

    /**
     * Generate structured AI content post grounded in Brand Brain and active Strategy.
     */
    public function generatePost(TenantContext $context, array $params): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.create');

        // 1. Quota check and consumption per brand
        $this->entitlementService->assertCanAndConsume($context->organizationId, 'ai_content', 1, $context->brandId);

        // 2. Build strategic context
        $contentContext = $this->contextBuilder->build(
            organizationId: $context->organizationId,
            pillarId: isset($params['pillar_id']) ? (int) $params['pillar_id'] : null,
            campaignThemeId: isset($params['campaign_theme_id']) ? (int) $params['campaign_theme_id'] : null,
            productId: isset($params['product_id']) ? (int) $params['product_id'] : null,
            audienceId: isset($params['audience_id']) ? (int) $params['audience_id'] : null,
            platform: $params['primary_platform'] ?? 'linkedin',
            tone: $params['tone'] ?? null,
            dialect: $params['dialect'] ?? null,
            language: $params['language'] ?? 'ar',
            brandId: $context->brandId
        );

        // 3. Generate structured post
        $generated = $this->generatorAgent->generate(
            context: $contentContext,
            userPrompt: $params['prompt'] ?? null,
            contentType: $params['content_type'] ?? 'post'
        );

        // 4. Generate platform variations
        $targetPlatforms = $params['target_platforms'] ?? ['linkedin', 'instagram', 'x', 'facebook', 'tiktok'];
        $variations = $this->repurposingService->repurpose($generated, $targetPlatforms);

        // 5. Run Quality & Safety Audit
        $auditData = $this->qualityAgent->audit($generated, $contentContext['brand'] ?? []);

        // 6. Atomically persist Post, Variations, and Audit
        return DB::transaction(function () use ($context, $params, $contentContext, $generated, $variations, $auditData) {
            $post = ContentPostModel::create([
                'organization_id' => $context->organizationId,
                'brand_profile_id' => $context->brandId ?? ($contentContext['brand']['id'] ?? null),
                'strategy_id' => $contentContext['strategic_anchor']['strategy_id'] ?? null,
                'pillar_id' => $contentContext['strategic_anchor']['pillar_id'] ?? null,
                'campaign_theme_id' => $contentContext['strategic_anchor']['theme_id'] ?? null,
                'title' => $generated['title'],
                'hook' => $generated['hook'],
                'caption' => $generated['caption'],
                'cta' => $generated['cta'],
                'hashtags' => $generated['hashtags'],
                'primary_platform' => $params['primary_platform'] ?? 'linkedin',
                'content_type' => $params['content_type'] ?? 'post',
                'language' => $params['language'] ?? 'ar',
                'dialect' => $params['dialect'] ?? ($contentContext['brand']['dialect'] ?? 'saudi'),
                'tone' => $params['tone'] ?? ($contentContext['brand']['tone'] ?? 'professional'),
                'objective' => $params['objective'] ?? 'engagement',
                'visual_brief' => $generated['visual_brief'],
                'status' => 'draft',
                'created_by' => $context->userId,
            ]);

            // Save Variations
            foreach ($variations as $platformKey => $varData) {
                ContentVariationModel::create([
                    'organization_id' => $context->organizationId,
                    'content_post_id' => $post->id,
                    'platform' => $varData['platform'],
                    'format' => $varData['format'],
                    'hook' => $varData['hook'],
                    'body' => $varData['body'],
                    'cta' => $varData['cta'],
                    'hashtags' => $varData['hashtags'],
                    'visual_brief' => $varData['visual_brief'],
                    'thread_slides' => $varData['thread_slides'],
                    'character_count' => $varData['character_count'],
                    'status' => $varData['status'],
                ]);
            }

            // Save Quality Audit
            ContentQualityAuditModel::create([
                'organization_id' => $context->organizationId,
                'content_post_id' => $post->id,
                'score' => $auditData['score'],
                'brand_alignment_score' => $auditData['brand_alignment_score'],
                'hook_strength_score' => $auditData['hook_strength_score'],
                'clarity_score' => $auditData['clarity_score'],
                'safety_compliance_score' => $auditData['safety_compliance_score'],
                'strengths' => $auditData['strengths'],
                'warnings' => $auditData['warnings'],
                'suggestions' => $auditData['suggestions'],
                'passed_restrictions' => $auditData['passed_restrictions'],
            ]);

            $this->auditService->log(
                action: 'content.generated',
                organizationId: $context->organizationId,
                userId: $context->userId,
                entityType: 'content_post',
                entityId: (string) $post->id
            );

            return $post->load(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
        });
    }

    /**
     * Update an existing content post and re-run quality audit.
     */
    public function updatePost(TenantContext $context, int $postId, array $data): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.update');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $post->update($data);

        $profile = \App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel::where('organization_id', $context->organizationId)->first();
        $voice = \App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel::where('organization_id', $context->organizationId)->first();
        $forbidden = array_merge(
            $voice?->forbidden_phrases ?? [],
            $voice?->words_to_avoid ?? []
        );

        // Update or re-run quality audit if copy modified
        $auditData = $this->qualityAgent->audit([
            'title' => $post->title,
            'hook' => $post->hook,
            'caption' => $post->caption,
            'cta' => $post->cta,
            'hashtags' => $post->hashtags,
            'language' => $post->language,
            'primary_platform' => $post->primary_platform,
        ], [
            'restrictions' => $forbidden,
            'vocabulary_blacklist' => $forbidden,
        ]);

        ContentQualityAuditModel::create([
            'organization_id' => $context->organizationId,
            'content_post_id' => $post->id,
            'score' => $auditData['score'],
            'brand_alignment_score' => $auditData['brand_alignment_score'],
            'hook_strength_score' => $auditData['hook_strength_score'],
            'clarity_score' => $auditData['clarity_score'],
            'safety_compliance_score' => $auditData['safety_compliance_score'],
            'strengths' => $auditData['strengths'],
            'warnings' => $auditData['warnings'],
            'suggestions' => $auditData['suggestions'],
            'passed_restrictions' => $auditData['passed_restrictions'],
        ]);

        $this->auditService->log(
            action: 'content.updated',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_post',
            entityId: (string) $post->id
        );

        return $post->fresh(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
    }

    /**
     * Update or save a specific platform variation for a post.
     */
    public function updateVariation(TenantContext $context, int $postId, string $platform, array $data): ContentVariationModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.update');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $variation = ContentVariationModel::where('organization_id', $context->organizationId)
            ->where('content_post_id', $postId)
            ->where('platform', $platform)
            ->first();

        if (isset($data['body'])) {
            $data['character_count'] = mb_strlen($data['body']);
        }

        if ($variation) {
            $variation->update($data);
        } else {
            $data['organization_id'] = $context->organizationId;
            $data['content_post_id'] = $postId;
            $data['platform'] = $platform;
            $variation = ContentVariationModel::create($data);
        }

        return $variation;
    }

    /**
     * Regenerate specific post component (e.g. hook, caption, CTA, visual brief).
     */
    public function regenerateComponent(TenantContext $context, int $postId, string $component, ?string $instruction = null): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.update');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $dialect = $post->dialect ?? 'saudi';

        switch ($component) {
            case 'hook':
                $hooks = $dialect === 'saudi'
                    ? ["فكرة غير تقليدية لمضاعفة مبيعاتك في وقت قياسي 🚀", "هل تعرف ما الذي يميز العلامات التجارية الأكثر تأثيراً؟", "سر بسيط يصنع فارقاً حقيقياً في نمو مشروعك 👇"]
                    : ["A high-impact framework to scale your operations rapidly 🚀", "The #1 factor separating industry leaders from the rest.", "Here is the exact method to unlock exponential organic growth 👇"];
                $post->hook = $hooks[array_rand($hooks)];
                break;

            case 'cta':
                $post->cta = $dialect === 'saudi'
                    ? "وش رأيك بهذه الاستراتيجية؟ شاركنا وجهة نظرك في التعليقات أو راسلنا للبدء! 💡"
                    : "What is your main takeaway from this approach? Share in the comments below or contact us to get started! 💡";
                break;

            case 'visual_brief':
                $post->visual_brief = [
                    'type' => 'bold_quote_card',
                    'description' => "تصميم بصري جذاب يركز على الاقتباس المحوري للعنوان مع شعار العلامة وخلفية أنيقة متدرجة.",
                    'suggested_text_overlay' => $post->hook,
                    'color_notes' => "ألوان الهوية الرسمية مع تباين عالي لقراءة مريحة.",
                ];
                break;
        }

        $post->save();

        return $this->updatePost($context, $postId, []);
    }

    /**
     * Repurpose post into all supported platform formats.
     */
    public function repurposePost(TenantContext $context, int $postId, array $platforms): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.update');

        $post = $this->getPost($context, $postId);

        $repurposed = $this->repurposingService->repurpose([
            'title' => $post->title,
            'hook' => $post->hook,
            'caption' => $post->caption,
            'cta' => $post->cta,
            'hashtags' => $post->hashtags,
            'visual_brief' => $post->visual_brief,
            'language' => $post->language,
        ], $platforms);

        foreach ($repurposed as $platformKey => $varData) {
            ContentVariationModel::updateOrCreate(
                [
                    'organization_id' => $context->organizationId,
                    'content_post_id' => $post->id,
                    'platform' => $platformKey,
                ],
                [
                    'format' => $varData['format'],
                    'hook' => $varData['hook'],
                    'body' => $varData['body'],
                    'cta' => $varData['cta'],
                    'hashtags' => $varData['hashtags'],
                    'visual_brief' => $varData['visual_brief'],
                    'thread_slides' => $varData['thread_slides'],
                    'character_count' => $varData['character_count'],
                    'status' => 'ready',
                ]
            );
        }

        // Delete variations that are not in requested platform list
        ContentVariationModel::where('organization_id', $context->organizationId)
            ->where('content_post_id', $post->id)
            ->whereNotIn('platform', $platforms)
            ->delete();

        return $post->fresh(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
    }

    /**
     * Run on-demand quality and compliance audit.
     */
    public function runQualityAudit(TenantContext $context, int $postId): ContentQualityAuditModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.view');

        $post = $this->getPost($context, $postId);
        $profile = \App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel::where('organization_id', $context->organizationId)->first();
        $voice = \App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel::where('organization_id', $context->organizationId)->first();
        $forbidden = array_merge(
            $voice?->forbidden_phrases ?? [],
            $voice?->words_to_avoid ?? []
        );

        $auditData = $this->qualityAgent->audit([
            'title' => $post->title,
            'hook' => $post->hook,
            'caption' => $post->caption,
            'cta' => $post->cta,
            'hashtags' => $post->hashtags,
            'language' => $post->language,
            'primary_platform' => $post->primary_platform,
        ], [
            'restrictions' => $forbidden,
            'vocabulary_blacklist' => $forbidden,
        ]);

        return ContentQualityAuditModel::create([
            'organization_id' => $context->organizationId,
            'content_post_id' => $post->id,
            'score' => $auditData['score'],
            'brand_alignment_score' => $auditData['brand_alignment_score'],
            'hook_strength_score' => $auditData['hook_strength_score'],
            'clarity_score' => $auditData['clarity_score'],
            'safety_compliance_score' => $auditData['safety_compliance_score'],
            'strengths' => $auditData['strengths'],
            'warnings' => $auditData['warnings'],
            'suggestions' => $auditData['suggestions'],
            'passed_restrictions' => $auditData['passed_restrictions'],
        ]);
    }

    /**
     * Approve content post for scheduling / publication.
     */
    public function approvePost(TenantContext $context, int $postId): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.approve');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $post->update(['status' => 'approved']);

        $this->auditService->log(
            action: 'content.approved',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_post',
            entityId: (string) $post->id
        );

        return $post->fresh(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
    }

    /**
     * Schedule a content post for a specific date and time.
     */
    public function schedulePost(TenantContext $context, int $postId, string $scheduledAt): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.approve');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $post->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);

        $this->auditService->log(
            action: 'content.scheduled',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_post',
            entityId: (string) $post->id
        );

        return $post->fresh(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
    }

    /**
     * Delete a content post and its associated variations.
     */
    public function deletePost(TenantContext $context, int $postId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'content.delete');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $post->delete();

        $this->auditService->log(
            action: 'content.deleted',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_post',
            entityId: (string) $postId
        );

        return true;
    }
}
