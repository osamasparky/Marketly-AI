<?php

namespace Tests\Feature;

use App\Domains\Analytics\Infrastructure\Persistence\Models\AiRecommendationModel;
use App\Domains\Analytics\Infrastructure\Persistence\Models\PostMetricModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentPillarModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OrganizationModel $organization;
    private string $token;
    private MarketingStrategyModel $strategy;
    private ContentPillarModel $pillar;
    private ContentPostModel $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->user = User::factory()->create(['email' => 'analytics-owner@marketly.test']);
        $this->organization = OrganizationModel::create([
            'name' => 'Metrics Media Lab',
            'slug' => 'metrics-media-lab',
            'type' => 'business',
            'status' => 'active',
        ]);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();
        $this->organization->users()->attach($this->user->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->token = $this->user->createToken('analytics-token')->plainTextToken;

        $this->strategy = MarketingStrategyModel::create([
            'organization_id' => $this->organization->id,
            'name' => 'Q4 Growth Sprint',
            'status' => 'active',
            'version' => 1,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);

        $this->pillar = ContentPillarModel::create([
            'organization_id' => $this->organization->id,
            'strategy_id' => $this->strategy->id,
            'name' => 'SaaS Product Architecture',
            'objective' => 'education',
            'recommended_percentage' => 40,
            'status' => 'active',
        ]);

        $this->post = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'strategy_id' => $this->strategy->id,
            'pillar_id' => $this->pillar->id,
            'title' => 'Building Scalable Multi-Tenant Platforms',
            'hook' => 'Why traditional single-tenant SaaS models fail at scale.',
            'caption' => 'Deep architectural breakdown of database tenancy.',
            'primary_platform' => 'linkedin',
            'status' => 'published',
        ]);

        SocialAccountModel::create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'platform' => 'linkedin',
            'account_name' => 'Scale Lab Official',
            'account_id' => 'urn:li:889900',
            'access_token' => 'ln_token_enc',
            'is_active' => true,
            'health_status' => 'healthy',
        ]);
    }

    public function test_sync_analytics_generates_metrics_and_recommendations(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/analytics/sync');

        $response->assertStatus(200);
        $response->assertJsonPath('data.sync.posts_synced', 1);
        $response->assertJsonPath('data.sync.accounts_synced', 1);

        $this->assertDatabaseHas('post_metrics', [
            'organization_id' => $this->organization->id,
            'content_post_id' => $this->post->id,
        ]);

        $this->assertDatabaseHas('ai_recommendations', [
            'organization_id' => $this->organization->id,
            'type' => 'winning_hook',
            'status' => 'active',
        ]);
    }

    public function test_get_overview_and_pillar_performance(): void
    {
        // First sync
        $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/analytics/sync');

        // Overview
        $overviewRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/analytics/overview');

        $overviewRes->assertStatus(200);
        $overviewRes->assertJsonStructure([
            'data' => [
                'kpis' => [
                    'total_reach',
                    'total_impressions',
                    'total_engagements',
                    'avg_engagement_rate',
                    'total_clicks',
                    'total_followers',
                    'published_posts_count',
                ],
                'channels',
                'active_recommendations_count',
            ],
        ]);

        // Pillars performance
        $pillarRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/analytics/pillars');

        $pillarRes->assertStatus(200);
        $this->assertNotEmpty($pillarRes->json('data'));
        $this->assertEquals($this->pillar->id, $pillarRes->json('data.0.pillar_id'));
    }

    public function test_recommendation_apply_and_dismiss(): void
    {
        $rec = AiRecommendationModel::create([
            'organization_id' => $this->organization->id,
            'strategy_id' => $this->strategy->id,
            'type' => 'optimal_time',
            'title' => 'Post at 14:00 GST',
            'explanation' => 'Audience shows high response rate.',
            'status' => 'active',
        ]);

        // Apply recommendation
        $applyRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/analytics/recommendations/{$rec->id}/apply");

        $applyRes->assertStatus(200);
        $applyRes->assertJsonPath('data.status', 'applied');
        $this->assertEquals('applied', $rec->fresh()->status);
        $this->assertNotNull($rec->fresh()->applied_at);

        // Dismiss another recommendation
        $rec2 = AiRecommendationModel::create([
            'organization_id' => $this->organization->id,
            'strategy_id' => $this->strategy->id,
            'type' => 'content_fatigue',
            'title' => 'Vary promotional copy',
            'explanation' => 'Promotional fatigue detected.',
            'status' => 'active',
        ]);

        $dismissRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/analytics/recommendations/{$rec2->id}/dismiss");

        $dismissRes->assertStatus(200);
        $dismissRes->assertJsonPath('data.status', 'dismissed');
        $this->assertEquals('dismissed', $rec2->fresh()->status);
    }
}
