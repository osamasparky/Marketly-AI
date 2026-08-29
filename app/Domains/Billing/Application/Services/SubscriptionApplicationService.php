<?php

namespace App\Domains\Billing\Application\Services;

use App\Domains\Billing\Domain\Services\EntitlementService;
use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\SubscriptionModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriptionApplicationService
{
    public function __construct(
        private readonly EntitlementService $entitlementService,
        private readonly AuditApplicationService $auditService
    ) {}

    /**
     * List all active public plans with entitlements.
     */
    public function listPlans(): Collection
    {
        return PlanModel::with('entitlements')
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get current organization subscription and usage breakdown.
     */
    public function getSubscriptionDetails(TenantContext $context): array
    {
        TenantIsolationGuard::assertPermission($context, 'organization.view');

        $subscription = $this->entitlementService->getOrCreateDefaultSubscription($context->organizationId);

        $features = ['brand_brain', 'ai_strategy', 'ai_content', 'team_members', 'social_accounts', 'analytics', 'automation'];
        $usage = [];
        foreach ($features as $feature) {
            $usage[$feature] = $this->entitlementService->getRemainingUsage($context->organizationId, $feature);
        }

        return [
            'subscription' => $subscription->load('plan.entitlements'),
            'usage' => $usage,
            'trial_remaining_days' => $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture()
                ? Carbon::now()->diffInDays($subscription->trial_ends_at, false)
                : 0,
        ];
    }

    /**
     * Change or select a subscription tier for the organization.
     */
    public function selectPlan(TenantContext $context, int $planId): SubscriptionModel
    {
        TenantIsolationGuard::assertPermission($context, 'billing.manage');

        $plan = PlanModel::where('id', $planId)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($context, $plan) {
            $subscription = $this->entitlementService->getOrCreateDefaultSubscription($context->organizationId);

            $subscription->update([
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => Carbon::now()->startOfMonth(),
                'current_period_end' => Carbon::now()->endOfMonth(),
                'cancelled_at' => null,
            ]);

            $this->auditService->log(
                action: 'subscription.plan_changed',
                organizationId: $context->organizationId,
                userId: $context->userId,
                entityType: 'subscription',
                entityId: (string) $subscription->id
            );

            return $subscription->fresh(['plan.entitlements']);
        });
    }

    /**
     * Cancel recurring subscription.
     */
    public function cancelSubscription(TenantContext $context): SubscriptionModel
    {
        TenantIsolationGuard::assertPermission($context, 'billing.manage');

        return DB::transaction(function () use ($context) {
            $subscription = $this->entitlementService->getOrCreateDefaultSubscription($context->organizationId);

            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
            ]);

            $this->auditService->log(
                action: 'subscription.cancelled',
                organizationId: $context->organizationId,
                userId: $context->userId,
                entityType: 'subscription',
                entityId: (string) $subscription->id
            );

            return $subscription->fresh(['plan.entitlements']);
        });
    }
}
