<?php

namespace Tests\Feature;

use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingAndEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $owner;
    private UserModel $viewer;
    private OrganizationModel $orgA;
    private OrganizationModel $orgB;
    private string $ownerToken;
    private string $viewerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->orgA = OrganizationModel::create(['name' => 'Acme Corporation', 'slug' => 'acme-corp']);
        $this->orgB = OrganizationModel::create(['name' => 'Competitor Inc', 'slug' => 'competitor-inc']);

        $ownerRole = RoleModel::where('slug', 'owner')->first();
        $viewerRole = RoleModel::where('slug', 'viewer')->first();

        $this->owner = UserModel::factory()->create(['email' => 'owner@acme.com']);
        $this->orgA->memberships()->create([
            'user_id' => $this->owner->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
        $this->owner->update(['current_organization_id' => $this->orgA->id]);
        $this->ownerToken = $this->owner->createToken('owner')->plainTextToken;

        $this->viewer = UserModel::factory()->create(['email' => 'viewer@acme.com']);
        $this->orgA->memberships()->create([
            'user_id' => $this->viewer->id,
            'role_id' => $viewerRole->id,
            'status' => 'active',
        ]);
        $this->viewer->update(['current_organization_id' => $this->orgA->id]);
        $this->viewerToken = $this->viewer->createToken('viewer')->plainTextToken;
    }

    public function test_public_plans_endpoint_returns_available_tiers(): void
    {
        $response = $this->getJson('/api/v1/billing/plans');

        $response->assertStatus(200);
        $response->assertJsonPath('data.plans.0.slug', 'starter');
        $response->assertJsonPath('data.plans.1.slug', 'growth');
        $response->assertJsonPath('data.plans.2.slug', 'pro');
    }

    public function test_organization_subscription_auto_initializes_with_trial(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/billing/subscription');

        $response->assertStatus(200);
        $response->assertJsonPath('data.subscription.status', 'trialing');
        $response->assertJsonPath('data.subscription.plan.slug', 'starter');
        $response->assertJsonPath('data.usage.ai_strategy.is_enabled', true);
        $this->assertEquals(5, $response->json('data.usage.ai_strategy.limit'));
    }

    public function test_owner_can_upgrade_plan(): void
    {
        $growthPlan = PlanModel::where('slug', 'growth')->first();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/billing/subscription/select-plan', [
            'plan_id' => $growthPlan->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.subscription.status', 'active');
        $response->assertJsonPath('data.subscription.plan_id', $growthPlan->id);

        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $this->orgA->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
        ]);
    }

    public function test_viewer_role_cannot_change_plan(): void
    {
        $growthPlan = PlanModel::where('slug', 'growth')->first();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/billing/subscription/select-plan', [
            'plan_id' => $growthPlan->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_cross_tenant_billing_isolation(): void
    {
        // Owner of Org A attempts to query using Org B ID in header
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->orgB->id,
        ])->getJson('/api/v1/billing/subscription');

        $response->assertStatus(403);
    }

    public function test_cancel_subscription(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/billing/subscription/cancel');

        $response->assertStatus(200);
        $response->assertJsonPath('data.subscription.status', 'cancelled');
    }
}
