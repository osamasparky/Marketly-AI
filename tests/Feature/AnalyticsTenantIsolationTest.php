<?php

namespace Tests\Feature;

use App\Domains\Analytics\Infrastructure\Persistence\Models\AiRecommendationModel;
use App\Domains\Analytics\Infrastructure\Persistence\Models\PostMetricModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private OrganizationModel $orgA;
    private OrganizationModel $orgB;
    private string $tokenA;
    private string $tokenB;
    private ContentPostModel $postA;
    private ContentPostModel $postB;
    private AiRecommendationModel $recB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();

        // Org A
        $this->userA = User::factory()->create(['email' => 'tenant-a-analytics@marketly.test']);
        $this->orgA = OrganizationModel::create(['name' => 'Analytics Org A', 'slug' => 'an-org-a', 'type' => 'business', 'status' => 'active']);
        $this->orgA->users()->attach($this->userA->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenA = $this->userA->createToken('token-a')->plainTextToken;

        // Org B
        $this->userB = User::factory()->create(['email' => 'tenant-b-analytics@marketly.test']);
        $this->orgB = OrganizationModel::create(['name' => 'Analytics Org B', 'slug' => 'an-org-b', 'type' => 'business', 'status' => 'active']);
        $this->orgB->users()->attach($this->userB->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenB = $this->userB->createToken('token-b')->plainTextToken;

        $this->postA = ContentPostModel::create([
            'organization_id' => $this->orgA->id,
            'title' => 'Org A Confidential Post',
            'caption' => 'Confidential strategy.',
            'primary_platform' => 'linkedin',
            'status' => 'published',
        ]);

        PostMetricModel::create([
            'organization_id' => $this->orgA->id,
            'content_post_id' => $this->postA->id,
            'captured_at' => now(),
            'views' => 500,
            'reach' => 450,
            'likes' => 35,
            'engagement_rate' => 7.8,
        ]);

        $this->postB = ContentPostModel::create([
            'organization_id' => $this->orgB->id,
            'title' => 'Org B Stealth Launch',
            'caption' => 'Secret metrics.',
            'primary_platform' => 'instagram',
            'status' => 'published',
        ]);

        PostMetricModel::create([
            'organization_id' => $this->orgB->id,
            'content_post_id' => $this->postB->id,
            'captured_at' => now(),
            'views' => 2500,
            'reach' => 2200,
            'likes' => 180,
            'engagement_rate' => 8.2,
        ]);

        $this->recB = AiRecommendationModel::create([
            'organization_id' => $this->orgB->id,
            'type' => 'winning_hook',
            'title' => 'Org B Secret Strategy Hook',
            'explanation' => 'Proprietary formula.',
            'status' => 'active',
        ]);
    }

    public function test_tenant_a_cannot_apply_or_dismiss_tenant_b_recommendation(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson("/api/v1/analytics/recommendations/{$this->recB->id}/apply");

        $response->assertStatus(404);
        $this->assertEquals('active', $this->recB->fresh()->status);
    }

    public function test_tenant_a_overview_does_not_leak_tenant_b_metrics(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/analytics/overview');

        $response->assertStatus(200);
        $this->assertEquals(450, $response->json('data.kpis.total_reach'));
        $this->assertEquals(500, $response->json('data.kpis.total_impressions'));
    }

    public function test_tenant_a_content_list_does_not_contain_tenant_b_post_metrics(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/analytics/content');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->postA->id, $response->json('data.0.content_post_id'));
    }
}
