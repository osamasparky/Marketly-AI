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

class SuperAdminPlanAndSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $regularUser;
    private OrganizationModel $organization;
    private string $superAdminToken;
    private string $regularToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@marketly.ai',
            'is_super_admin' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'email' => 'client@acme.com',
            'is_super_admin' => false,
        ]);

        $this->organization = OrganizationModel::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'type' => 'business',
            'status' => 'active',
        ]);

        $ownerRole = DB::table('roles')->where('slug', 'owner')->first();
        $this->organization->users()->attach($this->regularUser->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->superAdminToken = $this->superAdmin->createToken('admin-token')->plainTextToken;
        $this->regularToken = $this->regularUser->createToken('client-token')->plainTextToken;
    }

    public function test_super_admin_can_list_all_plans(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
        ])->getJson('/api/v1/super-admin/plans');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'plans' => [
                    '*' => ['id', 'name', 'slug', 'price_monthly', 'entitlements'],
                ],
            ],
        ]);
        $this->assertGreaterThanOrEqual(3, count($response->json('data.plans')));
    }

    public function test_super_admin_can_create_new_plan(): void
    {
        $payload = [
            'name' => 'Agency Scale Ultra',
            'slug' => 'agency-scale-ultra',
            'description' => 'Dedicated tier for large scale marketing agencies.',
            'price_monthly' => 1299.00,
            'price_annual' => 12470.00,
            'currency' => 'SAR',
            'trial_days' => 30,
            'is_active' => true,
            'entitlements' => [
                ['feature_key' => 'brand_brain', 'is_enabled' => true, 'limit_count' => -1],
                ['feature_key' => 'ai_strategy', 'is_enabled' => true, 'limit_count' => 100],
                ['feature_key' => 'ai_content', 'is_enabled' => true, 'limit_count' => 1000],
                ['feature_key' => 'social_accounts', 'is_enabled' => true, 'limit_count' => 50],
                ['feature_key' => 'team_members', 'is_enabled' => true, 'limit_count' => 25],
                ['feature_key' => 'analytics', 'is_enabled' => true, 'limit_count' => -1],
                ['feature_key' => 'automation', 'is_enabled' => true, 'limit_count' => -1],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
        ])->postJson('/api/v1/super-admin/plans', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.plan.slug', 'agency-scale-ultra');
        $response->assertJsonPath('data.plan.price_monthly', 1299);

        // Verify public billing endpoint now includes the new plan
        $publicRes = $this->getJson('/api/v1/billing/plans');
        $publicRes->assertStatus(200);
        $slugs = collect($publicRes->json('data.plans'))->pluck('slug')->toArray();
        $this->assertContains('agency-scale-ultra', $slugs);
    }

    public function test_super_admin_can_update_plan_and_entitlements(): void
    {
        $plan = PlanModel::where('slug', 'growth')->first();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
        ])->patchJson("/api/v1/super-admin/plans/{$plan->id}", [
            'name' => 'Growth Plus Max',
            'price_monthly' => 349.00,
            'entitlements' => [
                ['feature_key' => 'social_accounts', 'is_enabled' => true, 'limit_count' => 10], // Updated limit from 5 to 10
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.plan.name', 'Growth Plus Max');

        $socialEnt = $plan->fresh()->entitlements()->where('feature_key', 'social_accounts')->first();
        $this->assertEquals(10, $socialEnt->limit_count);
    }

    public function test_super_admin_can_delete_unused_plan(): void
    {
        $plan = PlanModel::create([
            'name' => 'Temporary Promo Plan',
            'slug' => 'temp-promo-plan',
            'price_monthly' => 99,
            'price_annual' => 990,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
        ])->deleteJson("/api/v1/super-admin/plans/{$plan->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_super_admin_deactivates_plan_with_active_subscribers(): void
    {
        $starterPlan = PlanModel::where('slug', 'starter')->first();

        SubscriptionModel::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $starterPlan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
        ])->deleteJson("/api/v1/super-admin/plans/{$starterPlan->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.deactivated', true);
        $this->assertFalse($starterPlan->fresh()->is_active);
    }

    public function test_regular_user_cannot_update_site_settings(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->regularToken}",
        ])->patchJson('/api/v1/super-admin/site-settings', [
            'settings' => ['hero_title_ar' => 'Hack Attempt'],
        ]);

        $response->assertStatus(403);
    }

    public function test_public_and_super_admin_site_settings(): void
    {
        // 1. Public user can view site settings
        $publicRes = $this->getJson('/api/v1/site-settings');
        $publicRes->assertStatus(200);
        $this->assertNotEmpty($publicRes->json('data.settings.hero_title_ar'));

        // 2. Super Admin can update site settings
        $updateRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->superAdminToken}",
        ])->patchJson('/api/v1/super-admin/site-settings', [
            'settings' => [
                'hero_title_ar' => 'منصة التسويق الذاتي الرائدة 2026',
                'hero_title_en' => 'Autonomous Marketing Pioneer 2026',
                'contact_email' => 'support@marketly.ai',
            ],
        ]);

        $updateRes->assertStatus(200);
        $this->assertEquals('منصة التسويق الذاتي الرائدة 2026', $updateRes->json('data.settings.hero_title_ar'));
        $this->assertEquals('support@marketly.ai', $updateRes->json('data.settings.contact_email'));

        // 3. Verify public endpoint now serves updated settings immediately
        $freshPublic = $this->getJson('/api/v1/site-settings');
        $this->assertEquals('منصة التسويق الذاتي الرائدة 2026', $freshPublic->json('data.settings.hero_title_ar'));
    }
}
