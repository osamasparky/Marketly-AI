<?php

namespace App\Domains\Strategy\Controllers;

use App\Domains\Strategy\Application\Services\StrategyApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StrategyController extends Controller
{
    public function __construct(
        private readonly StrategyApplicationService $strategyService
    ) {}

    private function getContext(Request $request): TenantContext
    {
        return $request->attributes->get('tenant_context') ?? app(TenantContext::class);
    }

    /**
     * Get active or latest marketing strategy with health score.
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->strategyService->getActiveOrLatestStrategy($this->getContext($request));

        return ApiResponse::success(
            data: $result,
            meta: ['message' => 'Marketing strategy retrieved successfully.']
        );
    }

    /**
     * Show a specific strategy.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $strategy = $this->strategyService->getStrategy($this->getContext($request), $id);

        return ApiResponse::success(
            data: ['strategy' => $strategy]
        );
    }

    /**
     * Generate new AI Marketing Strategy draft from Brand Brain.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_objective' => 'required|string|in:lead_generation,brand_awareness,sales,engagement,community_growth,website_traffic,app_downloads,education',
            'target_platforms' => 'nullable|array',
            'target_platforms.*' => 'string|in:linkedin,instagram,x,tiktok,facebook,youtube',
            'time_horizon_months' => 'nullable|integer|min:1|max:12',
            'seasonal_focus' => 'nullable|string|max:100',
            'target_audience_id' => 'nullable|integer',
        ]);

        $strategy = $this->strategyService->generateStrategy($this->getContext($request), $validated);

        return ApiResponse::success(
            data: ['strategy' => $strategy],
            meta: ['message' => 'AI marketing strategy draft generated successfully.'],
            status: 201
        );
    }

    /**
     * Update strategy parameters.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:150',
            'description' => 'nullable|string|max:2000',
            'primary_objective' => 'sometimes|string|max:50',
            'secondary_objectives' => 'nullable|array',
            'rationale' => 'nullable|string|max:2000',
        ]);

        $strategy = $this->strategyService->updateStrategy($this->getContext($request), $id, $validated);

        return ApiResponse::success(
            data: ['strategy' => $strategy],
            meta: ['message' => 'Strategy updated successfully.']
        );
    }

    /**
     * Delete strategy.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->strategyService->deleteStrategy($this->getContext($request), $id);

        return ApiResponse::success(
            data: null,
            meta: ['message' => 'Strategy deleted successfully.']
        );
    }

    /**
     * Atomically activate strategy.
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $strategy = $this->strategyService->activateStrategy($this->getContext($request), $id);

        return ApiResponse::success(
            data: ['strategy' => $strategy],
            meta: ['message' => 'Marketing strategy activated successfully.']
        );
    }

    /**
     * Pause strategy.
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $strategy = $this->strategyService->pauseStrategy($this->getContext($request), $id);

        return ApiResponse::success(
            data: ['strategy' => $strategy],
            meta: ['message' => 'Marketing strategy paused.']
        );
    }

    /**
     * Archive strategy.
     */
    public function archive(Request $request, int $id): JsonResponse
    {
        $strategy = $this->strategyService->archiveStrategy($this->getContext($request), $id);

        return ApiResponse::success(
            data: ['strategy' => $strategy],
            meta: ['message' => 'Marketing strategy archived.']
        );
    }

    /**
     * Get strategy health breakdown.
     */
    public function health(Request $request, int $id): JsonResponse
    {
        $health = $this->strategyService->getStrategyHealth($this->getContext($request), $id);

        return ApiResponse::success(
            data: ['health' => $health]
        );
    }

    /**
     * Content Pillars CRUD
     */
    public function listPillars(Request $request, int $strategyId): JsonResponse
    {
        $strategy = $this->strategyService->getStrategy($this->getContext($request), $strategyId);

        return ApiResponse::success(['pillars' => $strategy->pillars]);
    }

    public function storePillar(Request $request, int $strategyId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'description' => 'nullable|string|max:1000',
            'objective' => 'nullable|string|max:50',
            'priority' => 'nullable|string|in:high,medium,low',
            'recommended_percentage' => 'required|integer|min:1|max:100',
        ]);

        $pillar = $this->strategyService->savePillar($this->getContext($request), $strategyId, $validated);

        return ApiResponse::success(
            data: ['pillar' => $pillar],
            meta: ['message' => 'Content pillar created.'],
            status: 201
        );
    }

    public function updatePillar(Request $request, int $strategyId, int $pillarId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'description' => 'nullable|string|max:1000',
            'objective' => 'nullable|string|max:50',
            'priority' => 'nullable|string|in:high,medium,low',
            'recommended_percentage' => 'sometimes|integer|min:1|max:100',
        ]);

        $pillar = $this->strategyService->savePillar($this->getContext($request), $strategyId, $validated, $pillarId);

        return ApiResponse::success(
            data: ['pillar' => $pillar],
            meta: ['message' => 'Content pillar updated.']
        );
    }

    public function deletePillar(Request $request, int $strategyId, int $pillarId): JsonResponse
    {
        $this->strategyService->deletePillar($this->getContext($request), $strategyId, $pillarId);

        return ApiResponse::success(
            data: null,
            meta: ['message' => 'Content pillar removed.']
        );
    }

    /**
     * Campaign Themes
     */
    public function listCampaignThemes(Request $request, int $strategyId): JsonResponse
    {
        $themes = $this->strategyService->getCampaignThemes($this->getContext($request), $strategyId);

        return ApiResponse::success(['campaign_themes' => $themes]);
    }

    public function storeCampaignTheme(Request $request, int $strategyId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'objective' => 'nullable|string|max:50',
            'audience_persona' => 'nullable|string|max:100',
            'core_message' => 'nullable|string|max:1000',
            'duration_weeks' => 'nullable|integer|min:1|max:52',
            'recommended_formats' => 'nullable|array',
        ]);

        $theme = $this->strategyService->saveCampaignTheme($this->getContext($request), $strategyId, $validated);

        return ApiResponse::success(
            data: ['campaign_theme' => $theme],
            meta: ['message' => 'Campaign theme created.'],
            status: 201
        );
    }

    /**
     * Opportunities
     */
    public function listOpportunities(Request $request, int $strategyId): JsonResponse
    {
        $opps = $this->strategyService->getOpportunities($this->getContext($request), $strategyId);

        return ApiResponse::success(['opportunities' => $opps]);
    }

    public function storeOpportunity(Request $request, int $strategyId): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:150',
            'description' => 'nullable|string|max:1000',
            'objective' => 'nullable|string|max:50',
            'priority' => 'nullable|string|in:high,medium,low',
            'source' => 'nullable|string|max:50',
            'recommended_timing' => 'nullable|string|max:100',
        ]);

        $opp = $this->strategyService->saveOpportunity($this->getContext($request), $strategyId, $validated);

        return ApiResponse::success(
            data: ['opportunity' => $opp],
            meta: ['message' => 'Opportunity created.'],
            status: 201
        );
    }
}
