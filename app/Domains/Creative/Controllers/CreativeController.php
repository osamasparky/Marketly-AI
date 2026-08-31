<?php

namespace App\Domains\Creative\Controllers;

use App\Domains\Creative\Application\Services\CreativeApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreativeController extends Controller
{
    public function __construct(
        private readonly CreativeApplicationService $creativeService
    ) {}

    /**
     * List media assets for the active tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $filters = $request->validate([
            'aspect_ratio' => ['nullable', 'string', Rule::in(['1:1', '4:5', '9:16', '16:9'])],
            'file_type' => ['nullable', 'string', Rule::in(['graphic_card', 'image', 'video_script', 'svg'])],
            'content_post_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $assets = $this->creativeService->getAssets($tenantContext, $filters);

        return response()->json([
            'data' => $assets,
            'meta' => [
                'total' => count($assets),
                'filters' => $filters,
            ],
        ]);
    }

    /**
     * Get a specific media asset.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $asset = $this->creativeService->getAsset($tenantContext, $id);

        return response()->json([
            'data' => $asset,
        ]);
    }

    /**
     * Generate visual graphic card asset.
     */
    public function generate(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'content_post_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'hook' => ['nullable', 'string'],
            'visual_style' => ['nullable', 'string', Rule::in([
                'product_showcase', 'lifestyle_scene', 'promotional_banner', 'quote_card', 
                'infographic_style', 'branded_quote', 'product_spotlight', 'metric_card', 
                'gradient_banner', 'card_graphic'
            ])],
            'aspect_ratio' => ['nullable', 'string', Rule::in(['1:1', '4:5', '9:16', '16:9'])],
            'color_palette' => ['nullable', 'array'],
            'visual_brief' => ['nullable', 'array'],
            'product_id' => ['nullable', 'integer'],
            'is_regeneration' => ['nullable', 'boolean'],
            'avoid_prompt' => ['nullable', 'string', 'max:500'],
        ]);

        $asset = $this->creativeService->generateVisual($tenantContext, $params);

        return response()->json([
            'message' => 'Visual creative asset generated successfully.',
            'data' => $asset,
        ], 201);
    }

    /**
     * Generate video reel script asset.
     */
    public function generateReel(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'content_post_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'hook' => ['nullable', 'string'],
            'dialect' => ['nullable', 'string', Rule::in(['saudi', 'msa', 'egyptian', 'uae', 'english'])],
            'prompt' => ['nullable', 'string', 'max:500'],
        ]);

        $asset = $this->creativeService->generateReelScript($tenantContext, $params);

        return response()->json([
            'message' => 'Video reel script generated successfully.',
            'data' => $asset,
        ], 201);
    }

    /**
     * Attach media asset to a content post.
     */
    public function attach(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'content_post_id' => ['required', 'integer'],
        ]);

        $asset = $this->creativeService->attachToPost($tenantContext, $id, (int) $params['content_post_id']);

        return response()->json([
            'message' => 'Media asset attached to post successfully.',
            'data' => $asset,
        ]);
    }

    /**
     * Delete media asset.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $this->creativeService->deleteAsset($tenantContext, $id);

        return response()->json([
            'message' => 'Media asset deleted successfully.',
        ]);
    }
}
