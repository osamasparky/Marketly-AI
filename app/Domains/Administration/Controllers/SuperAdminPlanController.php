<?php

namespace App\Domains\Administration\Controllers;

use App\Domains\Billing\Infrastructure\Persistence\Models\PlanEntitlementModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\SubscriptionModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperAdminPlanController extends Controller
{
    public function __construct(
        private readonly AuditApplicationService $auditService
    ) {}

    /**
     * List all subscription plans (active & inactive) with their entitlements.
     */
    public function index(): JsonResponse
    {
        $plans = PlanModel::with('entitlements')
            ->orderBy('price_monthly')
            ->get();

        return ApiResponse::success(
            data: ['plans' => $plans],
            meta: ['message' => 'Plans retrieved successfully.']
        );
    }

    /**
     * Create a new subscription plan with entitlements.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:plans,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_annual' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'entitlements' => ['nullable', 'array'],
            'entitlements.*.feature_key' => ['required_with:entitlements', 'string'],
            'entitlements.*.is_enabled' => ['required_with:entitlements', 'boolean'],
            'entitlements.*.limit_count' => ['required_with:entitlements', 'integer'],
        ]);

        $plan = DB::transaction(function () use ($validated, $request) {
            $plan = PlanModel::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['slug']),
                'description' => $validated['description'] ?? '',
                'price_monthly' => $validated['price_monthly'],
                'price_annual' => $validated['price_annual'],
                'currency' => $validated['currency'] ?? 'SAR',
                'trial_days' => $validated['trial_days'] ?? 14,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $defaultFeatures = [
                'brand_brain' => ['is_enabled' => true, 'limit_count' => -1],
                'ai_strategy' => ['is_enabled' => true, 'limit_count' => 10],
                'ai_content' => ['is_enabled' => true, 'limit_count' => 50],
                'team_members' => ['is_enabled' => true, 'limit_count' => 3],
                'social_accounts' => ['is_enabled' => true, 'limit_count' => 3],
                'analytics' => ['is_enabled' => true, 'limit_count' => -1],
                'automation' => ['is_enabled' => false, 'limit_count' => 0],
            ];

            $inputEntitlements = collect($validated['entitlements'] ?? [])->keyBy('feature_key');

            foreach ($defaultFeatures as $featureKey => $defaults) {
                $input = $inputEntitlements->get($featureKey);
                $isEnabled = $input ? (bool) $input['is_enabled'] : $defaults['is_enabled'];
                $limitCount = $input ? (int) $input['limit_count'] : $defaults['limit_count'];

                PlanEntitlementModel::create([
                    'plan_id' => $plan->id,
                    'feature_key' => $featureKey,
                    'is_enabled' => $isEnabled,
                    'limit_count' => $limitCount,
                ]);
            }

            return $plan;
        });

        $this->auditService->log(
            action: 'super_admin.plan_created',
            organizationId: null,
            userId: (int) auth()->id(),
            entityType: 'plan',
            entityId: (string) $plan->id,
            metadata: ['name' => $plan->name, 'slug' => $plan->slug]
        );

        return ApiResponse::success(
            data: ['plan' => $plan->load('entitlements')],
            meta: ['message' => 'Plan created successfully.'],
            status: 201
        );
    }

    /**
     * Update an existing subscription plan and sync its entitlements.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $plan = PlanModel::with('entitlements')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['sometimes', 'numeric', 'min:0'],
            'price_annual' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'entitlements' => ['nullable', 'array'],
            'entitlements.*.feature_key' => ['required_with:entitlements', 'string'],
            'entitlements.*.is_enabled' => ['required_with:entitlements', 'boolean'],
            'entitlements.*.limit_count' => ['required_with:entitlements', 'integer'],
        ]);

        DB::transaction(function () use ($plan, $validated) {
            $plan->update(collect($validated)->except('entitlements')->toArray());

            if (!empty($validated['entitlements'])) {
                foreach ($validated['entitlements'] as $item) {
                    PlanEntitlementModel::updateOrCreate(
                        [
                            'plan_id' => $plan->id,
                            'feature_key' => $item['feature_key'],
                        ],
                        [
                            'is_enabled' => (bool) $item['is_enabled'],
                            'limit_count' => (int) $item['limit_count'],
                        ]
                    );
                }
            }
        });

        $this->auditService->log(
            action: 'super_admin.plan_updated',
            organizationId: null,
            userId: (int) auth()->id(),
            entityType: 'plan',
            entityId: (string) $plan->id,
            metadata: ['name' => $plan->name]
        );

        return ApiResponse::success(
            data: ['plan' => $plan->fresh('entitlements')],
            meta: ['message' => 'Plan updated successfully.']
        );
    }

    /**
     * Delete or soft-deactivate a subscription plan.
     */
    public function destroy(int $id): JsonResponse
    {
        $plan = PlanModel::findOrFail($id);

        $activeSubscriptionsCount = SubscriptionModel::where('plan_id', $id)
            ->where('status', 'active')
            ->count();

        if ($activeSubscriptionsCount > 0) {
            // Soft-deactivate to protect current subscribers
            $plan->update(['is_active' => false]);

            $this->auditService->log(
                action: 'super_admin.plan_deactivated',
                organizationId: null,
                userId: (int) auth()->id(),
                entityType: 'plan',
                entityId: (string) $plan->id,
                metadata: ['active_subscribers' => $activeSubscriptionsCount]
            );

            return ApiResponse::success(
                data: ['plan' => $plan, 'deactivated' => true],
                meta: ['message' => "Plan has {$activeSubscriptionsCount} active subscribers so it was deactivated instead of deleted."]
            );
        }

        DB::transaction(function () use ($plan) {
            $plan->entitlements()->delete();
            $plan->delete();
        });

        $this->auditService->log(
            action: 'super_admin.plan_deleted',
            organizationId: null,
            userId: (int) auth()->id(),
            entityType: 'plan',
            entityId: (string) $id,
            metadata: ['slug' => $plan->slug]
        );

        return ApiResponse::success(
            data: null,
            meta: ['message' => 'Plan deleted successfully.']
        );
    }
}
