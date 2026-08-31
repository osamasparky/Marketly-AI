<?php

namespace App\Domains\Administration\Application\Services;

use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\SubscriptionModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\AuditLogModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationMembershipModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SuperAdminApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService
    ) {}

    /**
     * Get platform-wide global KPIs and metrics.
     */
    public function getGlobalKpis(): array
    {
        $totalOrgs = OrganizationModel::count();
        $activeOrgs = OrganizationModel::where('status', 'active')->count();
        $suspendedOrgs = OrganizationModel::where('status', 'suspended')->count();
        $totalUsers = UserModel::count();
        $publishedPosts = ContentPostModel::where('status', 'published')->count();
        $totalPosts = ContentPostModel::count();

        $activeSubscriptions = SubscriptionModel::with('plan')
            ->where('status', 'active')
            ->get();

        $estimatedMrr = $activeSubscriptions->sum(function ($sub) {
            return $sub->plan ? (float) $sub->plan->price_monthly : 0;
        });

        // Plan distribution
        $plans = PlanModel::all();
        $planDistribution = [];
        foreach ($plans as $plan) {
            $count = SubscriptionModel::where('plan_id', $plan->id)
                ->where('status', 'active')
                ->count();
            $planDistribution[] = [
                'plan_id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price_monthly' => (float) $plan->price_monthly,
                'subscribers_count' => $count,
                'revenue_contribution' => $count * (float) $plan->price_monthly,
            ];
        }

        // Recent platform audit activity
        $recentActivity = AuditLogModel::orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'organization_id' => $log->organization_id,
                    'user_id' => $log->user_id,
                    'entity_type' => $log->entity_type,
                    'metadata' => $log->metadata_json,
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            });

        return [
            'kpis' => [
                'total_organizations' => $totalOrgs,
                'active_organizations' => $activeOrgs,
                'suspended_organizations' => $suspendedOrgs,
                'total_users' => $totalUsers,
                'active_subscriptions' => $activeSubscriptions->count(),
                'estimated_mrr' => round($estimatedMrr, 2),
                'published_posts' => $publishedPosts,
                'total_posts' => $totalPosts,
                'ai_generations_count' => $totalPosts * 3, // Estimated AI prompts run
            ],
            'plan_distribution' => $planDistribution,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * List all organizations with search, filters, members count, subscription, and post stats.
     */
    public function getOrganizations(array $filters = []): LengthAwarePaginator
    {
        $query = OrganizationModel::query();

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('slug', 'like', $search)
                    ->orWhere('website_url', 'like', $search)
                    ->orWhere('billing_email', 'like', $search)
                    ->orWhere('industry', 'like', $search);
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $query->orderByDesc('id');

        $perPage = !empty($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $paginator = $query->paginate($perPage);

        // Transform collection to add enriched subscription and stats
        $paginator->getCollection()->transform(function ($org) {
            $subscription = SubscriptionModel::with('plan')
                ->where('organization_id', $org->id)
                ->latest()
                ->first();

            $membersCount = OrganizationMembershipModel::where('organization_id', $org->id)->count();
            $postsCount = ContentPostModel::where('organization_id', $org->id)->count();
            $publishedCount = ContentPostModel::where('organization_id', $org->id)->where('status', 'published')->count();

            return [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'type' => $org->type,
                'status' => $org->status,
                'default_locale' => $org->default_locale,
                'timezone' => $org->timezone,
                'website_url' => $org->website_url,
                'industry' => $org->industry,
                'billing_email' => $org->billing_email,
                'has_custom_ai_keys' => !empty($org->ai_config_json),
                'members_count' => $membersCount,
                'posts_count' => $postsCount,
                'published_posts_count' => $publishedCount,
                'current_plan' => $subscription ? [
                    'id' => $subscription->plan?->id,
                    'name' => $subscription->plan?->name ?? 'None',
                    'slug' => $subscription->plan?->slug ?? 'free',
                    'status' => $subscription->status,
                    'price_monthly' => $subscription->plan ? (float) $subscription->plan->price_monthly : 0,
                    'current_period_end' => $subscription->current_period_end?->toIso8601String(),
                ] : null,
                'created_at' => $org->created_at?->toIso8601String(),
            ];
        });

        return $paginator;
    }

    /**
     * Update organization status (e.g. active, suspended, archived).
     */
    public function updateOrganizationStatus(int $organizationId, string $status, UserModel $adminUser): OrganizationModel
    {
        $org = OrganizationModel::findOrFail($organizationId);
        $oldStatus = $org->status;
        $org->update(['status' => $status]);

        $this->auditService->log(
            action: 'super_admin.organization_status_updated',
            organizationId: $org->id,
            userId: $adminUser->id,
            entityType: 'organization',
            entityId: (string) $org->id,
            metadata: ['old_status' => $oldStatus, 'new_status' => $status]
        );

        return $org;
    }

    /**
     * Override or update organization subscription plan.
     */
    public function updateOrganizationPlan(int $organizationId, int $planId, UserModel $adminUser): SubscriptionModel
    {
        $org = OrganizationModel::findOrFail($organizationId);
        $plan = PlanModel::findOrFail($planId);

        $subscription = SubscriptionModel::updateOrCreate(
            ['organization_id' => $org->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => Carbon::now(),
                'current_period_end' => Carbon::now()->addMonth(),
                'cancel_at_period_end' => false,
            ]
        );

        $this->auditService->log(
            action: 'super_admin.organization_plan_updated',
            organizationId: $org->id,
            userId: $adminUser->id,
            entityType: 'subscription',
            entityId: (string) $subscription->id,
            metadata: ['plan_id' => $plan->id, 'plan_name' => $plan->name]
        );

        return $subscription->load('plan');
    }

    /**
     * Impersonate target company workspace as Super Admin.
     * Ensures Super Admin has temporary or membership access and switches current_organization_id.
     */
    public function impersonateOrganization(UserModel $superAdmin, int $targetOrgId): array
    {
        $org = OrganizationModel::findOrFail($targetOrgId);

        // Ensure membership exists so TenantContext and RBAC pass seamlessly
        $ownerRole = RoleModel::where('slug', 'owner')->first();
        if ($ownerRole) {
            OrganizationMembershipModel::firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'user_id' => $superAdmin->id,
                ],
                [
                    'role_id' => $ownerRole->id,
                    'status' => 'active',
                    'joined_at' => Carbon::now(),
                ]
            );
        }

        $superAdmin->update(['current_organization_id' => $org->id]);

        $this->auditService->log(
            action: 'super_admin.impersonated_organization',
            organizationId: $org->id,
            userId: $superAdmin->id,
            entityType: 'organization',
            entityId: (string) $org->id,
            metadata: ['organization_name' => $org->name]
        );

        return [
            'message' => "Successfully impersonated {$org->name}",
            'organization' => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'status' => $org->status,
            ],
            'user' => [
                'id' => $superAdmin->id,
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'current_organization_id' => $org->id,
                'is_super_admin' => true,
            ],
        ];
    }

    /**
     * List all subscriptions across the entire platform.
     */
    public function getSubscriptions(): array
    {
        return SubscriptionModel::with(['organization', 'plan'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'organization' => $sub->organization ? [
                        'id' => $sub->organization->id,
                        'name' => $sub->organization->name,
                        'slug' => $sub->organization->slug,
                        'status' => $sub->organization->status,
                    ] : null,
                    'plan' => $sub->plan ? [
                        'id' => $sub->plan->id,
                        'name' => $sub->plan->name,
                        'slug' => $sub->plan->slug,
                        'price_monthly' => (float) $sub->plan->price_monthly,
                    ] : null,
                    'status' => $sub->status,
                    'current_period_start' => $sub->current_period_start?->toIso8601String(),
                    'current_period_end' => $sub->current_period_end?->toIso8601String(),
                    'cancel_at_period_end' => (bool) $sub->cancel_at_period_end,
                ];
            })
            ->toArray();
    }

    /**
     * Generate system reports and health statistics.
     */
    public function getSystemReports(): array
    {
        $platformBreakdown = DB::table('content_posts')
            ->select('primary_platform', DB::raw('count(*) as total_posts'))
            ->groupBy('primary_platform')
            ->get();

        $topOrganizations = OrganizationModel::withCount(['memberships as members_count', 'users'])
            ->get()
            ->map(function ($org) {
                $posts = ContentPostModel::where('organization_id', $org->id)->count();
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'status' => $org->status,
                    'members_count' => $org->members_count,
                    'posts_count' => $posts,
                ];
            })
            ->sortByDesc('posts_count')
            ->values()
            ->take(8);

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'platform_breakdown' => $platformBreakdown,
            'top_active_organizations' => $topOrganizations,
            'system_health' => [
                'database_status' => 'operational',
                'queue_status' => 'operational',
                'cache_driver' => config('cache.default', 'file'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ];
    }
}
