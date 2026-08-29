<?php

namespace App\Domains\Billing\Controllers;

use App\Domains\Billing\Application\Services\SubscriptionApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        private readonly SubscriptionApplicationService $subscriptionService
    ) {}

    private function getContext(Request $request): TenantContext
    {
        return $request->attributes->get('tenant_context')
            ?? new TenantContext(
                userId: (int) auth()->id(),
                organizationId: (int) (auth()->user()?->current_organization_id ?? 0),
                role: 'viewer',
                permissions: []
            );
    }

    /**
     * List all public active subscription plans.
     */
    public function listPlans(): JsonResponse
    {
        $plans = $this->subscriptionService->listPlans();

        return ApiResponse::success(
            data: ['plans' => $plans],
            meta: ['message' => 'Plans retrieved successfully.']
        );
    }

    /**
     * Get current tenant subscription and usage statistics.
     */
    public function getSubscription(Request $request): JsonResponse
    {
        $details = $this->subscriptionService->getSubscriptionDetails($this->getContext($request));

        return ApiResponse::success(
            data: $details,
            meta: ['message' => 'Subscription details retrieved.']
        );
    }

    /**
     * Select or change subscription plan.
     */
    public function selectPlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $sub = $this->subscriptionService->selectPlan($this->getContext($request), (int) $validated['plan_id']);

        return ApiResponse::success(
            data: ['subscription' => $sub],
            meta: ['message' => 'Subscription updated successfully.']
        );
    }

    /**
     * Cancel active subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $sub = $this->subscriptionService->cancelSubscription($this->getContext($request));

        return ApiResponse::success(
            data: ['subscription' => $sub],
            meta: ['message' => 'Subscription cancelled.']
        );
    }
}
