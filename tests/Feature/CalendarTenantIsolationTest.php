<?php

namespace Tests\Feature;

use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTenantIsolationTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();

        // Org A
        $this->userA = User::factory()->create(['email' => 'tenant-a-cal@marketly.test']);
        $this->orgA = OrganizationModel::create(['name' => 'Calendar Org A', 'slug' => 'cal-org-a', 'type' => 'business', 'status' => 'active']);
        $this->orgA->users()->attach($this->userA->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenA = $this->userA->createToken('token-a')->plainTextToken;

        // Org B
        $this->userB = User::factory()->create(['email' => 'tenant-b-cal@marketly.test']);
        $this->orgB = OrganizationModel::create(['name' => 'Calendar Org B', 'slug' => 'cal-org-b', 'type' => 'business', 'status' => 'active']);
        $this->orgB->users()->attach($this->userB->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenB = $this->userB->createToken('token-b')->plainTextToken;

        $this->postA = ContentPostModel::create([
            'organization_id' => $this->orgA->id,
            'title' => 'Tenant A Secret Campaign',
            'caption' => 'Confidential post schedule.',
            'primary_platform' => 'linkedin',
            'status' => 'scheduled',
            'scheduled_at' => Carbon::now()->addDays(2)->toDateTimeString(),
        ]);

        $this->postB = ContentPostModel::create([
            'organization_id' => $this->orgB->id,
            'title' => 'Tenant B Secret Launch',
            'caption' => 'Confidential launch date.',
            'primary_platform' => 'instagram',
            'status' => 'scheduled',
            'scheduled_at' => Carbon::now()->addDays(4)->toDateTimeString(),
        ]);
    }

    public function test_tenant_a_cannot_reschedule_tenant_b_post(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson("/api/v1/calendar/posts/{$this->postB->id}/reschedule", [
            'scheduled_at' => Carbon::now()->addDays(10)->toDateTimeString(),
        ]);

        $response->assertStatus(404);
    }

    public function test_tenant_a_cannot_approve_tenant_b_post(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson("/api/v1/calendar/posts/{$this->postB->id}/approve");

        $response->assertStatus(404);
    }

    public function test_tenant_a_calendar_query_does_not_contain_tenant_b_posts(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/calendar');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.posts');
        $response->assertJsonPath('data.posts.0.id', $this->postA->id);
    }
}
