<?php

namespace App\Domains\Administration\Controllers;

use App\Domains\Administration\Application\Services\SuperAdminApplicationService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function __construct(
        private readonly SuperAdminApplicationService $adminService
    ) {}

    /**
     * Platform-wide global KPIs and charts.
     */
    public function kpis(): JsonResponse
    {
        $kpis = $this->adminService->getGlobalKpis();

        return ApiResponse::success(
            data: $kpis,
            meta: ['message' => 'Super admin KPIs retrieved.']
        );
    }

    /**
     * Paginated list of organizations.
     */
    public function organizations(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'per_page', 'page']);
        $paginator = $this->adminService->getOrganizations($filters);

        return ApiResponse::success(
            data: [
                'organizations' => $paginator->items(),
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
            meta: ['message' => 'Organizations retrieved successfully.']
        );
    }

    /**
     * Update an organization's platform status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:active,suspended,archived',
        ]);

        $org = $this->adminService->updateOrganizationStatus($id, $validated['status'], $request->user());

        return ApiResponse::success(
            data: ['organization' => $org],
            meta: ['message' => "Organization status updated to {$validated['status']}."]
        );
    }

    /**
     * Upgrade/downgrade/override an organization's subscription plan.
     */
    public function updatePlan(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $subscription = $this->adminService->updateOrganizationPlan($id, $validated['plan_id'], $request->user());

        return ApiResponse::success(
            data: ['subscription' => $subscription],
            meta: ['message' => 'Organization subscription plan updated successfully.']
        );
    }

    /**
     * 1-Click Login as Company (Impersonate).
     */
    public function impersonate(Request $request, int $id): JsonResponse
    {
        $result = $this->adminService->impersonateOrganization($request->user(), $id);

        return ApiResponse::success(
            data: $result,
            meta: ['message' => $result['message']]
        );
    }

    /**
     * List all platform subscriptions.
     */
    public function subscriptions(): JsonResponse
    {
        $subscriptions = $this->adminService->getSubscriptions();

        return ApiResponse::success(
            data: ['subscriptions' => $subscriptions],
            meta: ['message' => 'Subscriptions retrieved.']
        );
    }

    /**
     * System reports and diagnostic metrics.
     */
    public function reports(): JsonResponse
    {
        $reports = $this->adminService->getSystemReports();

        return ApiResponse::success(
            data: $reports,
            meta: ['message' => 'System reports generated.']
        );
    }
}
