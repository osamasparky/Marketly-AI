<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Application\Services\ContentApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    public function __construct(
        private readonly ContentApplicationService $contentService
    ) {}

    /**
     * List all posts for current tenant organization.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['draft', 'in_review', 'approved', 'scheduled', 'published', 'archived'])],
            'primary_platform' => ['nullable', 'string', Rule::in(['linkedin', 'instagram', 'x', 'facebook', 'tiktok'])],
            'pillar_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $posts = $this->contentService->getPosts($tenantContext, $filters);

        return response()->json([
            'data' => $posts,
            'meta' => [
                'total' => count($posts),
                'filters' => $filters,
            ],
        ]);
    }

    /**
     * Get a specific content post with variations and audits.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $post = $this->contentService->getPost($tenantContext, $id);

        return response()->json([
            'data' => $post,
        ]);
    }

    /**
     * Generate an AI content post grounded in Brand Brain and Strategy.
     */
    public function generate(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'pillar_id' => ['nullable', 'integer'],
            'campaign_theme_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'audience_id' => ['nullable', 'integer'],
            'primary_platform' => ['nullable', 'string', Rule::in(['linkedin', 'instagram', 'x', 'facebook', 'tiktok'])],
            'content_type' => ['nullable', 'string', Rule::in(['post', 'carousel', 'thread', 'reel_script', 'story'])],
            'language' => ['nullable', 'string', Rule::in(['ar', 'en'])],
            'dialect' => ['nullable', 'string', Rule::in(['msa', 'saudi', 'egyptian', 'uae', 'gulf', 'general'])],
            'tone' => ['nullable', 'string', Rule::in(['professional', 'conversational', 'witty', 'educational', 'direct_response'])],
            'objective' => ['nullable', 'string', Rule::in(['brand_awareness', 'lead_generation', 'sales', 'education', 'engagement'])],
            'prompt' => ['nullable', 'string', 'max:1000'],
            'target_platforms' => ['nullable', 'array'],
            'target_platforms.*' => ['string', Rule::in(['linkedin', 'instagram', 'x', 'facebook', 'tiktok'])],
        ]);

        $post = $this->contentService->generatePost($tenantContext, $params);

        return response()->json([
            'message' => 'Content post generated successfully.',
            'data' => $post,
        ], 201);
    }

    /**
     * Update an existing content post.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'hook' => ['nullable', 'string'],
            'caption' => ['sometimes', 'string'],
            'cta' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:50'],
            'primary_platform' => ['nullable', 'string', Rule::in(['linkedin', 'instagram', 'x', 'facebook', 'tiktok'])],
            'status' => ['nullable', 'string', Rule::in(['draft', 'in_review', 'approved', 'scheduled', 'published', 'archived'])],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $post = $this->contentService->updatePost($tenantContext, $id, $data);

        return response()->json([
            'message' => 'Post updated successfully.',
            'data' => $post,
        ]);
    }

    /**
     * Update specific platform variation.
     */
    public function updateVariation(Request $request, int $id, string $platform): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $data = $request->validate([
            'body' => ['required', 'string'],
            'hook' => ['nullable', 'string'],
            'cta' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'array'],
            'format' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'ready', 'approved'])],
        ]);

        $variation = $this->contentService->updateVariation($tenantContext, $id, $platform, $data);

        return response()->json([
            'message' => "Variation for {$platform} updated successfully.",
            'data' => $variation,
        ]);
    }

    /**
     * Regenerate specific component of post.
     */
    public function regenerate(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'component' => ['required', 'string', Rule::in(['hook', 'caption', 'cta', 'visual_brief'])],
            'instruction' => ['nullable', 'string', 'max:500'],
        ]);

        $post = $this->contentService->regenerateComponent($tenantContext, $id, $params['component'], $params['instruction'] ?? null);

        return response()->json([
            'message' => "Component [{$params['component']}] regenerated successfully.",
            'data' => $post,
        ]);
    }

    /**
     * Repurpose post across multiple platforms.
     */
    public function repurpose(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', Rule::in(['linkedin', 'instagram', 'x', 'facebook', 'tiktok'])],
        ]);

        $post = $this->contentService->repurposePost($tenantContext, $id, $params['platforms']);

        return response()->json([
            'message' => 'Post repurposed successfully across requested platforms.',
            'data' => $post,
        ]);
    }

    /**
     * Re-run quality and compliance audit.
     */
    public function qualityCheck(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $audit = $this->contentService->runQualityAudit($tenantContext, $id);

        return response()->json([
            'message' => 'Quality and compliance audit completed.',
            'data' => $audit,
        ]);
    }

    /**
     * Approve post for scheduling.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $post = $this->contentService->approvePost($tenantContext, $id);

        return response()->json([
            'message' => 'Post approved successfully.',
            'data' => $post,
        ]);
    }

    /**
     * Schedule post for publication.
     */
    public function schedule(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $post = $this->contentService->schedulePost($tenantContext, $id, $params['scheduled_at']);

        return response()->json([
            'message' => 'Post scheduled successfully.',
            'data' => $post,
        ]);
    }

    /**
     * Delete post.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $this->contentService->deletePost($tenantContext, $id);

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }
}
