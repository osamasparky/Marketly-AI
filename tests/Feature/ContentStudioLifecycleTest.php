<?php

namespace Tests\Feature;

use App\Domains\Billing\Domain\Services\EntitlementService;
use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentVariationModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentPillarModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentStudioLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OrganizationModel $organization;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->user = User::factory()->create(['email' => 'content-creator@marketly.test']);
        $this->organization = OrganizationModel::create([
            'name' => 'Growth Agency',
            'slug' => 'growth-agency',
            'type' => 'business',
            'status' => 'active',
        ]);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();
        $this->organization->users()->attach($this->user->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Setup Brand Profile & Voice
        $profile = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Growth Pulse',
            'industry' => 'SaaS Marketing',
            'description' => 'Automated marketing solutions for modern SaaS businesses.',
            'restrictions' => ['guaranteed 100x return', 'free crypto'],
        ]);

        BrandVoiceModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $profile->id,
            'primary_tones' => ['professional', 'educational'],
            'formality_scale' => 3,
            'dialect' => 'saudi',
            'forbidden_phrases' => ['scam', 'get rich quick'],
            'words_to_avoid' => ['ponzi'],
        ]);

        // Setup Active Strategy & Pillar
        $strategy = MarketingStrategyModel::create([
            'organization_id' => $this->organization->id,
            'name' => 'Q3 Growth Engine',
            'description' => 'Driving pipeline via educational content',
            'primary_objective' => 'lead_generation',
            'status' => 'active',
            'version' => 1,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);

        ContentPillarModel::create([
            'organization_id' => $this->organization->id,
            'strategy_id' => $strategy->id,
            'name' => 'Educational Case Studies',
            'description' => 'Breakdowns of SaaS growth tactics',
            'objective' => 'education',
            'priority' => 'high',
            'recommended_percentage' => 40,
            'status' => 'active',
        ]);
    }

    public function test_get_empty_content_posts(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/content');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_generate_ai_content_post_creates_post_variations_and_audit(): void
    {
        $pillar = ContentPillarModel::where('organization_id', $this->organization->id)->first();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/content/generate', [
            'pillar_id' => $pillar->id,
            'primary_platform' => 'linkedin',
            'language' => 'ar',
            'dialect' => 'saudi',
            'tone' => 'professional',
            'prompt' => 'Focus on operational efficiency and AI automation for B2B SaaS',
            'target_platforms' => ['linkedin', 'instagram', 'x', 'facebook', 'tiktok'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.primary_platform', 'linkedin');
        $response->assertJsonPath('data.status', 'draft');
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'hook',
                'caption',
                'cta',
                'hashtags',
                'visual_brief',
                'variations',
                'latest_audit' => [
                    'score',
                    'brand_alignment_score',
                    'hook_strength_score',
                    'clarity_score',
                    'safety_compliance_score',
                    'strengths',
                ],
            ],
        ]);

        $postId = $response->json('data.id');

        // Verify Database
        $this->assertDatabaseHas('content_posts', [
            'id' => $postId,
            'organization_id' => $this->organization->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseCount('content_variations', 5);
        $this->assertDatabaseHas('content_quality_audits', [
            'content_post_id' => $postId,
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_update_post_and_variation(): void
    {
        $pillar = ContentPillarModel::where('organization_id', $this->organization->id)->first();

        // 1. Generate post
        $genRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/content/generate', [
            'pillar_id' => $pillar->id,
            'primary_platform' => 'linkedin',
        ]);

        $postId = $genRes->json('data.id');

        // 2. Update post details
        $updateRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->patchJson("/api/v1/content/{$postId}", [
            'title' => 'Updated Growth Blueprint 2026',
            'caption' => 'Updated caption with deep strategic insights and clear execution roadmap.',
        ]);

        $updateRes->assertStatus(200);
        $updateRes->assertJsonPath('data.title', 'Updated Growth Blueprint 2026');

        // 3. Update specific variation
        $varRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->patchJson("/api/v1/content/{$postId}/variations/linkedin", [
            'body' => 'Customized tailored copy specifically for LinkedIn executive audience.',
            'hook' => 'Executive insights: How to scale efficiently.',
        ]);

        $varRes->assertStatus(200);
        $varRes->assertJsonPath('data.body', 'Customized tailored copy specifically for LinkedIn executive audience.');
    }

    public function test_regenerate_components_and_repurpose(): void
    {
        $pillar = ContentPillarModel::where('organization_id', $this->organization->id)->first();

        $genRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/content/generate', [
            'pillar_id' => $pillar->id,
        ]);

        $postId = $genRes->json('data.id');

        // Regenerate hook
        $regenRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/content/{$postId}/regenerate", [
            'component' => 'hook',
        ]);

        $regenRes->assertStatus(200);
        $this->assertNotEmpty($regenRes->json('data.hook'));

        // Repurpose
        $repurposeRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/content/{$postId}/repurpose", [
            'platforms' => ['linkedin', 'instagram', 'x', 'tiktok'],
        ]);

        $repurposeRes->assertStatus(200);
        $this->assertCount(4, $repurposeRes->json('data.variations'));
    }

    public function test_approve_and_schedule_post_lifecycle(): void
    {
        $pillar = ContentPillarModel::where('organization_id', $this->organization->id)->first();

        $genRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/content/generate', [
            'pillar_id' => $pillar->id,
        ]);

        $postId = $genRes->json('data.id');

        // Approve
        $approveRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/content/{$postId}/approve");

        $approveRes->assertStatus(200);
        $approveRes->assertJsonPath('data.status', 'approved');

        // Schedule
        $scheduleTime = now()->addDays(2)->toDateTimeString();
        $schedRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/content/{$postId}/schedule", [
            'scheduled_at' => $scheduleTime,
        ]);

        $schedRes->assertStatus(200);
        $schedRes->assertJsonPath('data.status', 'scheduled');

        // Delete
        $delRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->deleteJson("/api/v1/content/{$postId}");

        $delRes->assertStatus(200);
        $this->assertDatabaseMissing('content_posts', ['id' => $postId]);
        $this->assertDatabaseMissing('content_variations', ['content_post_id' => $postId]);
    }
}
