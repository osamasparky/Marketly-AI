<?php

namespace Tests\Feature;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentPillarModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OrganizationModel $organization;
    private string $token;
    private MarketingStrategyModel $strategy;
    private ContentPillarModel $pillar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->user = User::factory()->create(['email' => 'calendar-mgr@marketly.test']);
        $this->organization = OrganizationModel::create([
            'name' => 'Omni Brand Media',
            'slug' => 'omni-brand-media',
            'type' => 'business',
            'status' => 'active',
        ]);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();
        $this->organization->users()->attach($this->user->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;

        BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Pulse Tech',
            'industry' => 'Cloud Computing',
        ]);

        $this->strategy = MarketingStrategyModel::create([
            'organization_id' => $this->organization->id,
            'name' => 'Cloud Domination Q3',
            'primary_objective' => 'lead_generation',
            'status' => 'active',
            'version' => 1,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);

        $this->pillar = ContentPillarModel::create([
            'organization_id' => $this->organization->id,
            'strategy_id' => $this->strategy->id,
            'name' => 'DevOps Automation Guides',
            'objective' => 'education',
            'priority' => 'high',
            'recommended_percentage' => 50,
            'status' => 'active',
        ]);
    }

    public function test_get_empty_calendar_returns_clean_structure(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/calendar');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'start_date',
                'end_date',
                'metrics' => [
                    'total_scheduled',
                    'draft_count',
                    'in_review_count',
                    'approved_count',
                    'scheduled_count',
                    'published_count',
                ],
                'posts',
            ],
        ]);
    }

    public function test_auto_plan_generates_multi_day_content_calendar(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/calendar/plan', [
            'horizon_days' => 7,
            'platforms' => ['linkedin', 'instagram', 'x'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.horizon_days', 7);
        $response->assertJsonPath('data.posts_created', 7);
        $this->assertCount(7, $response->json('data.posts'));

        // Verify Database
        $this->assertDatabaseCount('content_posts', 7);
        $this->assertDatabaseCount('content_variations', 35); // 7 posts * 5 platforms
    }

    public function test_drag_and_drop_reschedule_post(): void
    {
        $post = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'title' => 'DevOps Best Practices',
            'caption' => 'Infrastructure as Code best practices in 2026.',
            'primary_platform' => 'linkedin',
            'status' => 'approved',
            'scheduled_at' => Carbon::now()->addDays(1)->toDateTimeString(),
        ]);

        $newDate = Carbon::now()->addDays(5)->setTime(15, 30, 0)->toDateTimeString();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$post->id}/reschedule", [
            'scheduled_at' => $newDate,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'scheduled');
        $this->assertEquals($newDate, Carbon::parse($post->fresh()->scheduled_at)->toDateTimeString());
    }

    public function test_approval_state_machine_workflow(): void
    {
        $post = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'title' => 'Cloud Scalability',
            'caption' => 'Deep dive into container orchestration.',
            'primary_platform' => 'linkedin',
            'status' => 'draft',
        ]);

        // 1. Submit for review (draft -> in_review)
        $reviewRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$post->id}/submit-review");
        $reviewRes->assertStatus(200);
        $reviewRes->assertJsonPath('data.status', 'in_review');

        // 2. Approve (in_review -> approved)
        $approveRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$post->id}/approve");
        $approveRes->assertStatus(200);
        $approveRes->assertJsonPath('data.status', 'approved');

        // 3. Schedule (approved -> scheduled)
        $schedTime = Carbon::now()->addDays(3)->toDateTimeString();
        $scheduleRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$post->id}/schedule", [
            'scheduled_at' => $schedTime,
        ]);
        $scheduleRes->assertStatus(200);
        $scheduleRes->assertJsonPath('data.status', 'scheduled');

        // 4. Unschedule (scheduled -> draft)
        $unschedRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$post->id}/unschedule");
        $unschedRes->assertStatus(200);
        $unschedRes->assertJsonPath('data.status', 'draft');
        $this->assertNull($post->fresh()->scheduled_at);
    }
}
