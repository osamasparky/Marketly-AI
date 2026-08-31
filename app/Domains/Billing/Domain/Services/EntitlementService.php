<?php

namespace App\Domains\Billing\Domain\Services;

use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\SubscriptionModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\UsageRecordModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EntitlementService
{
    /**
     * Check if organization has active entitlement for a feature.
     */
    public function can(int $organizationId, string $featureKey): bool
    {
        $subscription = $this->getOrCreateDefaultSubscription($organizationId);

        if (!$subscription->isActive()) {
            return false;
        }

        $entitlement = $subscription->plan?->entitlements()
            ->where('feature_key', $featureKey)
            ->first();

        return (bool) ($entitlement?->is_enabled ?? false);
    }

    /**
     * Get remaining usage and limits for a specific feature in current monthly period.
     * Calculated per brand independently if brandProfileId is provided.
     */
    public function getRemainingUsage(int $organizationId, string $featureKey, ?int $brandProfileId = null): array
    {
        $subscription = $this->getOrCreateDefaultSubscription($organizationId);
        $entitlement = $subscription->plan?->entitlements()
            ->where('feature_key', $featureKey)
            ->first();

        $isEnabled = (bool) ($entitlement?->is_enabled ?? false);
        $limit = $entitlement?->limit_count ?? 0;
        $isUnlimited = $limit === -1;

        $periodStart = Carbon::now()->startOfMonth()->toDateString();
        $periodEnd = Carbon::now()->endOfMonth()->toDateString();

        $query = UsageRecordModel::where('organization_id', $organizationId)
            ->where('feature_key', $featureKey)
            ->whereDate('period_start', $periodStart);

        if ($brandProfileId) {
            $query->where('brand_profile_id', $brandProfileId);
        } else {
            $query->whereNull('brand_profile_id');
        }

        $usage = $query->first();

        $used = $usage?->used_count ?? 0;
        $remaining = $isUnlimited ? 999999 : max(0, $limit - $used);

        return [
            'feature_key' => $featureKey,
            'is_enabled' => $isEnabled,
            'is_unlimited' => $isUnlimited,
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ];
    }

    /**
     * Verify entitlement and atomically consume usage (scoped per brand).
     */
    public function assertCanAndConsume(int $organizationId, string $featureKey, int $amount = 1, ?int $brandProfileId = null): void
    {
        if (!$this->can($organizationId, $featureKey)) {
            throw new HttpException(403, "Feature [{$featureKey}] is not included in your current subscription plan.");
        }

        $usageInfo = $this->getRemainingUsage($organizationId, $featureKey, $brandProfileId);

        if (!$usageInfo['is_unlimited'] && $usageInfo['remaining'] < $amount) {
            throw new HttpException(403, "You have reached your monthly limit for [{$featureKey}]. Please upgrade your plan.");
        }

        $this->consume($organizationId, $featureKey, $amount, $brandProfileId);
    }

    /**
     * Verify organization has quota to create another brand.
     */
    public function assertCanCreateBrand(int $organizationId): void
    {
        $subscription = $this->getOrCreateDefaultSubscription($organizationId);
        $entitlement = $subscription->plan?->entitlements()
            ->where('feature_key', 'brands')
            ->first();

        $limit = $entitlement?->limit_count ?? 1;

        if ($limit === -1) {
            return; // Unlimited brands
        }

        $currentCount = \App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel::where('organization_id', $organizationId)->count();

        if ($currentCount >= $limit) {
            throw new HttpException(403, "Your plan allows a maximum of {$limit} brand(s). Please upgrade to Growth or Pro to manage multiple brands.");
        }
    }

    /**
     * Verify organization/brand can connect additional social account.
     */
    public function assertCanConnectSocialAccount(int $organizationId, ?int $brandProfileId = null): void
    {
        $subscription = $this->getOrCreateDefaultSubscription($organizationId);
        $entitlement = $subscription->plan?->entitlements()
            ->where('feature_key', 'social_accounts')
            ->first();

        if (!$entitlement || !$entitlement->is_enabled) {
            throw new HttpException(403, "Your current subscription plan ({$subscription->plan->name}) does not support connecting social media accounts. Please upgrade your plan.");
        }

        $limit = $entitlement->limit_count;
        if ($limit === -1) {
            return; // Unlimited accounts
        }

        $query = \App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel::where('organization_id', $organizationId)
            ->where('is_active', true);

        if ($brandProfileId) {
            $query->where('brand_profile_id', $brandProfileId);
        }

        $currentAccounts = $query->count();

        if ($currentAccounts >= $limit) {
            throw new HttpException(403, "You have reached your plan limit of {$limit} connected social account(s). Please upgrade your subscription to connect more channels.");
        }
    }

    /**
     * Atomically increment usage record.
     */
    public function consume(int $organizationId, string $featureKey, int $amount = 1, ?int $brandProfileId = null): bool
    {
        $periodStart = Carbon::now()->startOfMonth()->toDateString();
        $periodEnd = Carbon::now()->endOfMonth()->toDateString();

        return DB::transaction(function () use ($organizationId, $featureKey, $periodStart, $periodEnd, $amount, $brandProfileId) {
            $query = UsageRecordModel::where('organization_id', $organizationId)
                ->where('feature_key', $featureKey)
                ->whereDate('period_start', $periodStart);

            if ($brandProfileId) {
                $query->where('brand_profile_id', $brandProfileId);
            } else {
                $query->whereNull('brand_profile_id');
            }

            $record = $query->lockForUpdate()->first();

            if ($record) {
                $record->increment('used_count', $amount);
            } else {
                UsageRecordModel::create([
                    'organization_id' => $organizationId,
                    'brand_profile_id' => $brandProfileId,
                    'feature_key' => $featureKey,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'used_count' => $amount,
                ]);
            }

            return true;
        });
    }

    /**
     * Retrieve or automatically initialize a default 14-day Starter trial subscription with entitlements.
     */
    public function getOrCreateDefaultSubscription(int $organizationId): SubscriptionModel
    {
        $sub = SubscriptionModel::with('plan.entitlements')
            ->where('organization_id', $organizationId)
            ->first();

        if ($sub) {
            return $sub;
        }

        $starterPlan = PlanModel::where('slug', 'starter')->first();

        if (!$starterPlan) {
            $starterPlan = PlanModel::create([
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Starter trial tier',
                'price_monthly' => 0.00,
                'price_annual' => 0.00,
                'currency' => 'SAR',
                'trial_days' => 14,
                'is_active' => true,
            ]);

            $starterPlan->entitlements()->createMany([
                ['feature_key' => 'brand_brain', 'is_enabled' => true, 'limit_count' => -1],
                ['feature_key' => 'brands', 'is_enabled' => true, 'limit_count' => 1],
                ['feature_key' => 'ai_strategy', 'is_enabled' => true, 'limit_count' => 5],
                ['feature_key' => 'ai_content', 'is_enabled' => true, 'limit_count' => 30],
                ['feature_key' => 'team_members', 'is_enabled' => true, 'limit_count' => 2],
            ]);
        }

        return SubscriptionModel::create([
            'organization_id' => $organizationId,
            'plan_id' => $starterPlan->id,
            'status' => 'trialing',
            'trial_ends_at' => Carbon::now()->addDays($starterPlan->trial_days),
            'current_period_start' => Carbon::now()->startOfMonth(),
            'current_period_end' => Carbon::now()->endOfMonth(),
        ])->load('plan.entitlements');
    }
}
