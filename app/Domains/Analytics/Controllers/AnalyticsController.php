<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Application\Services\AnalyticsApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsApplicationService $analyticsService
    ) {}

    /**
     * Executive overview KPIs and channel statistics.
     */
    public function overview(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $result = $this->analyticsService->getOverview($tenantContext);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Top-performing published content ranked by engagement.
     */
    public function content(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $result = $this->analyticsService->getContentPerformance($tenantContext);

        return response()->json($result);
    }

    /**
     * Pillar performance attribution.
     */
    public function pillars(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $result = $this->analyticsService->getPillarPerformance($tenantContext);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Live metrics synchronization and AI learnings generation.
     */
    public function sync(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $result = $this->analyticsService->syncAnalytics($tenantContext);

        return response()->json([
            'message' => 'Analytics synchronization completed successfully.',
            'data' => $result,
        ]);
    }

    /**
     * List AI recommendations and learnings.
     */
    public function recommendations(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $result = $this->analyticsService->getRecommendations($tenantContext);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Apply an AI recommendation.
     */
    public function applyRecommendation(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $rec = $this->analyticsService->applyRecommendation($tenantContext, $id);

        return response()->json([
            'message' => 'Recommendation applied successfully.',
            'data' => $rec,
        ]);
    }

    /**
     * Dismiss an AI recommendation.
     */
    public function dismissRecommendation(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $rec = $this->analyticsService->dismissRecommendation($tenantContext, $id);

        return response()->json([
            'message' => 'Recommendation dismissed.',
            'data' => $rec,
        ]);
    }
}
