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

class CalendarSecurityAndRbacTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationModel $organization;
    private User $viewerUser;
    private User $editorUser;
    private User $managerUser;
    private string $viewerToken;
    private string $editorToken;
    private string $managerToken;
    private ContentPostModel $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->organization = OrganizationModel::create([
            'name' => 'Calendar RBAC Org',
            'slug' => 'cal-rbac-org',
            'type' => 'business',
            'status' => 'active',
        ]);

        $viewerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'viewer')->first();
        $editorRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'editor')->first();
        $managerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'manager')->first();

        // Viewer User
        $this->viewerUser = User::factory()->create(['email' => 'viewer-cal@marketly.test']);
        $this->organization->users()->attach($this->viewerUser->id, ['role_id' => $viewerRole->id, 'status' => 'active']);
        $this->viewerToken = $this->viewerUser->createToken('viewer-token')->plainTextToken;

        // Editor User
        $this->editorUser = User::factory()->create(['email' => 'editor-cal@marketly.test']);
        $this->organization->users()->attach($this->editorUser->id, ['role_id' => $editorRole->id, 'status' => 'active']);
        $this->editorToken = $this->editorUser->createToken('editor-token')->plainTextToken;

        // Manager User
        $this->managerUser = User::factory()->create(['email' => 'manager-cal@marketly.test']);
        $this->organization->users()->attach($this->managerUser->id, ['role_id' => $managerRole->id, 'status' => 'active']);
        $this->managerToken = $this->managerUser->createToken('manager-token')->plainTextToken;

        $this->post = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'title' => 'Product Feature Review',
            'caption' => 'Deep dive into performance.',
            'primary_platform' => 'linkedin',
            'status' => 'draft',
        ]);
    }

    public function test_viewer_can_view_calendar_but_cannot_reschedule_or_approve(): void
    {
        // 1. Viewer can view
        $viewRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/calendar');
        $viewRes->assertStatus(200);

        // 2. Viewer cannot reschedule
        $reschedRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$this->post->id}/reschedule", [
            'scheduled_at' => Carbon::now()->addDays(3)->toDateTimeString(),
        ]);
        $reschedRes->assertStatus(403);
    }

    public function test_editor_can_submit_for_review_but_cannot_approve(): void
    {
        // 1. Editor can submit for review
        $reviewRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->editorToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$this->post->id}/submit-review");
        $reviewRes->assertStatus(200);
        $reviewRes->assertJsonPath('data.status', 'in_review');

        // 2. Editor CANNOT approve (requires Manager/Admin/Owner)
        $approveRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->editorToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$this->post->id}/approve");
        $approveRes->assertStatus(403);
    }

    public function test_manager_can_approve_and_schedule(): void
    {
        // Manager can approve
        $approveRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->managerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$this->post->id}/approve");
        $approveRes->assertStatus(200);
        $approveRes->assertJsonPath('data.status', 'approved');

        // Manager can schedule
        $schedRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->managerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/calendar/posts/{$this->post->id}/schedule", [
            'scheduled_at' => Carbon::now()->addDays(2)->toDateTimeString(),
        ]);
        $schedRes->assertStatus(200);
        $schedRes->assertJsonPath('data.status', 'scheduled');
    }
}
