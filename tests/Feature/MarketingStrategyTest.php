<?php

namespace Tests\Feature;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingStrategyTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $owner;
    private OrganizationModel $org;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->owner = UserModel::factory()->create(['email' => 'strategist@brand.ai']);
        $this->token = $this->owner->createToken('test')->plainTextToken;

        $this->org = OrganizationModel::create(['name' => 'Acme Labs', 'slug' => 'acme-labs']);
        $ownerRole = RoleModel::where('slug', 'owner')->first();
        $this->org->memberships()->create([
            'user_id' => $this->owner->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
        $this->owner->update(['current_organization_id' => $this->org->id]);

        // Seed basic brand profile
        BrandProfileModel::create([
            'organization_id' => $this->org->id,
            'business_name' => 'Acme Labs',
            'industry' => 'Technology',
            'business_type' => 'B2B',
            'description' => 'Autonomous marketing AI suite.',
        ]);
    }

    public function test_get_empty_strategy_returns_empty_health(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->getJson('/api/v1/strategy');

        $response->assertStatus(200);
        $response->assertJsonPath('data.health.total_score', 0);
        $response->assertJsonPath('data.health.status', 'empty');
    }

    public function test_generate_ai_strategy_creates_draft_with_pillars_and_themes(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson('/api/v1/strategy/generate', [
            'primary_objective' => 'lead_generation',
            'target_platforms' => ['linkedin', 'instagram'],
            'time_horizon_months' => 3,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.strategy.status', 'draft');
        $response->assertJsonPath('data.strategy.primary_objective', 'lead_generation');

        $strategyId = $response->json('data.strategy.id');

        $this->assertDatabaseHas('marketing_strategies', [
            'id' => $strategyId,
            'organization_id' => $this->org->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('content_pillars', [
            'strategy_id' => $strategyId,
            'organization_id' => $this->org->id,
        ]);

        $this->assertDatabaseHas('campaign_themes', [
            'strategy_id' => $strategyId,
            'organization_id' => $this->org->id,
        ]);

        $this->assertDatabaseHas('content_opportunities', [
            'strategy_id' => $strategyId,
            'organization_id' => $this->org->id,
        ]);
    }

    public function test_transactional_strategy_activation_pauses_previous_active_strategy(): void
    {
        // 1. Create first strategy and activate it
        $strat1 = MarketingStrategyModel::create([
            'organization_id' => $this->org->id,
            'name' => 'Strategy 1',
            'primary_objective' => 'sales',
            'status' => 'active',
            'version' => 1,
        ]);

        // 2. Create second strategy in draft
        $strat2 = MarketingStrategyModel::create([
            'organization_id' => $this->org->id,
            'name' => 'Strategy 2',
            'primary_objective' => 'brand_awareness',
            'status' => 'draft',
            'version' => 1,
        ]);

        // 3. Activate strategy 2
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson("/api/v1/strategy/{$strat2->id}/activate");

        $res->assertStatus(200);
        $res->assertJsonPath('data.strategy.status', 'active');

        // Verify Strategy 1 is now paused and Strategy 2 is active
        $this->assertEquals('paused', $strat1->fresh()->status);
        $this->assertEquals('active', $strat2->fresh()->status);
    }

    public function test_strategy_lifecycle_pause_archive_and_delete(): void
    {
        $strat = MarketingStrategyModel::create([
            'organization_id' => $this->org->id,
            'name' => 'Lifecycle Strategy',
            'primary_objective' => 'sales',
            'status' => 'active',
            'version' => 1,
        ]);

        // Pause
        $pauseRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson("/api/v1/strategy/{$strat->id}/pause");
        $pauseRes->assertStatus(200);
        $this->assertEquals('paused', $strat->fresh()->status);

        // Archive
        $archiveRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson("/api/v1/strategy/{$strat->id}/archive");
        $archiveRes->assertStatus(200);
        $this->assertEquals('archived', $strat->fresh()->status);

        // Delete
        $delRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->deleteJson("/api/v1/strategy/{$strat->id}");
        $delRes->assertStatus(200);
        $this->assertDatabaseMissing('marketing_strategies', ['id' => $strat->id]);
    }

    public function test_content_pillars_and_campaign_themes_crud(): void
    {
        $strat = MarketingStrategyModel::create([
            'organization_id' => $this->org->id,
            'name' => 'Pillar Strategy',
            'primary_objective' => 'sales',
            'status' => 'draft',
            'version' => 1,
        ]);

        // Create Pillar
        $pillarRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson("/api/v1/strategy/{$strat->id}/pillars", [
            'name' => 'Product Demo',
            'recommended_percentage' => 30,
            'objective' => 'sales',
            'priority' => 'high',
        ]);
        $pillarRes->assertStatus(201);
        $pillarId = $pillarRes->json('data.pillar.id');

        // Update Pillar
        $patchPillar = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->patchJson("/api/v1/strategy/{$strat->id}/pillars/{$pillarId}", [
            'recommended_percentage' => 40,
        ]);
        $patchPillar->assertStatus(200);
        $this->assertEquals(40, $patchPillar->json('data.pillar.recommended_percentage'));

        // Create Campaign Theme
        $themeRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson("/api/v1/strategy/{$strat->id}/campaign-themes", [
            'name' => 'Spring Growth Launch',
            'core_message' => 'Scale 10x faster with AI automation.',
            'duration_weeks' => 6,
        ]);
        $themeRes->assertStatus(201);

        // Delete Pillar
        $delPillar = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->deleteJson("/api/v1/strategy/{$strat->id}/pillars/{$pillarId}");
        $delPillar->assertStatus(200);
    }
}
