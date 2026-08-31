<?php

namespace Tests\Feature;

use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\SubscriptionModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminAccessAndImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $regularUser;
    private OrganizationModel $organizationA;
    private OrganizationModel $organizationB;
    private string $superAdminToken;
    private string $regularToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        // 1. Create Super Admin User
        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@marketly.test',
            'is_super_admin' => true,
        ]);

        // 2. Create Regular User
        $this->regularUser = User::factory()->create([
            'email' => 'regular@clientcorp.test',
            'is_super_admin' => false,
        ]);

        // 3. Create Organizations
        $this->organizationA = OrganizationModel::create([
            'name' => 'Marketly HQ',
            'slug' => 'marketly-hq',
            'type' => 'agency',
            'status' => 'active',
        ]);

        $this->organizationB = OrganizationModel::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'type' => 'business',
            'status' => 'active',
        ]);

        $ownerRole = DB::table('roles')->where('slug', 'owner')->first();

        $this->organizationA->users()->attach($this->superAdmin->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->organizationB->users()->attach($this->regularUser->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->superAdmin->update(['current_organization_id' => $this->organizationA->id]);
        $this->regularUser->update(['current_organization_id' => $this->organizationB->id]);

        $this->superAdminToken = $this->superAdmin->createToken('super-admin-token')->plainTextToken;
        $this->regularToken = $this->regularUser->createToken('regular-token')->plainTextToken;
    }

    public function test_regular_user_is_forbidden_from_super_admin_endpoints(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->regularToken}",
            'X-Organization-Id' => (string) $this->organizationB->id,
        ])->getJson('/api/v1/super-admin/kpis');

        $response->assertStatus(403)
            ->assertJson([
                'code' => 'SUPER_ADMIN_REQUIRED',
            ]);
    }

    public function test_super_admin_can_view_global_kpis(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->getJson('/api/v1/super-admin/kpis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'kpis' => [
                        'total_organizations',
                        'active_organizations',
                        'total_users',
                        'estimated_mrr',
                    ],
                    'plan_distribution',
                    'recent_activity',
                ],
                'meta',
            ]);

        $this->assertGreaterThanOrEqual(2, $response->json('data.kpis.total_organizations'));
    }

    public function test_super_admin_can_list_and_filter_organizations(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->getJson('/api/v1/super-admin/organizations?search=Acme');

        $response->assertStatus(200)
            ->assertJsonPath('data.organizations.0.name', 'Acme Corporation')
            ->assertJsonStructure([
                'data' => [
                    'organizations' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'connected_social_accounts_count',
                            'social_accounts_limit',
                            'ai_content_used_this_month',
                            'ai_content_limit',
                            'current_plan',
                        ]
                    ]
                ]
            ]);
    }

    public function test_super_admin_can_update_organization_status(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->patchJson("/api/v1/super-admin/organizations/{$this->organizationB->id}/status", [
            'status' => 'suspended',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.organization.status', 'suspended');

        $this->assertEquals('suspended', $this->organizationB->fresh()->status);
    }

    public function test_super_admin_can_update_organization_plan(): void
    {
        $enterprisePlan = PlanModel::where('slug', 'enterprise')->first();
        if (!$enterprisePlan) {
            $enterprisePlan = PlanModel::create([
                'name' => 'Enterprise Plan',
                'slug' => 'enterprise',
                'price_monthly' => 299.00,
                'price_yearly' => 2990.00,
                'currency' => 'USD',
                'is_active' => true,
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->patchJson("/api/v1/super-admin/organizations/{$this->organizationB->id}/plan", [
            'plan_id' => $enterprisePlan->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.subscription.plan_id', $enterprisePlan->id);

        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $this->organizationB->id,
            'plan_id' => $enterprisePlan->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_impersonate_and_login_as_company(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->postJson("/api/v1/super-admin/organizations/{$this->organizationB->id}/impersonate");

        $response->assertStatus(200)
            ->assertJsonPath('data.organization.id', $this->organizationB->id)
            ->assertJsonPath('data.user.current_organization_id', $this->organizationB->id);

        // Verify Super Admin's current organization was switched
        $this->assertEquals($this->organizationB->id, $this->superAdmin->fresh()->current_organization_id);

        // Verify Super Admin can now access organization B's resources seamlessly
        $meRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
            'X-Organization-Id' => (string) $this->organizationB->id,
        ])->getJson('/api/v1/me');

        $meRes->assertStatus(200)
            ->assertJsonPath('data.current_organization.id', $this->organizationB->id)
            ->assertJsonPath('data.user.is_super_admin', true);
    }

    public function test_super_admin_can_view_system_reports_and_subscriptions(): void
    {
        $subRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->getJson('/api/v1/super-admin/subscriptions');

        $subRes->assertStatus(200)
            ->assertJsonStructure(['data' => ['subscriptions']]);

        $repRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->getJson('/api/v1/super-admin/reports');

        $repRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'generated_at',
                    'platform_breakdown',
                    'top_active_organizations',
                    'system_health',
                ],
            ]);
    }
}
