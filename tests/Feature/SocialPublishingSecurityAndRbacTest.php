<?php

namespace Tests\Feature;

use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPublishingSecurityAndRbacTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationModel $organization;
    private User $viewerUser;
    private User $editorUser;
    private User $managerUser;
    private string $viewerToken;
    private string $editorToken;
    private string $managerToken;
    private SocialAccountModel $account;
    private ContentPostModel $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->organization = OrganizationModel::create([
            'name' => 'Social RBAC Org',
            'slug' => 'soc-rbac-org',
            'type' => 'business',
            'status' => 'active',
        ]);

        $viewerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'viewer')->first();
        $editorRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'editor')->first();
        $managerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'manager')->first();

        // Viewer User
        $this->viewerUser = User::factory()->create(['email' => 'viewer-soc@marketly.test']);
        $this->organization->users()->attach($this->viewerUser->id, ['role_id' => $viewerRole->id, 'status' => 'active']);
        $this->viewerToken = $this->viewerUser->createToken('viewer-token')->plainTextToken;

        // Editor User
        $this->editorUser = User::factory()->create(['email' => 'editor-soc@marketly.test']);
        $this->organization->users()->attach($this->editorUser->id, ['role_id' => $editorRole->id, 'status' => 'active']);
        $this->editorToken = $this->editorUser->createToken('editor-token')->plainTextToken;

        // Manager User
        $this->managerUser = User::factory()->create(['email' => 'manager-soc@marketly.test']);
        $this->organization->users()->attach($this->managerUser->id, ['role_id' => $managerRole->id, 'status' => 'active']);
        $this->managerToken = $this->managerUser->createToken('manager-token')->plainTextToken;

        $this->account = SocialAccountModel::create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->managerUser->id,
            'platform' => 'linkedin',
            'account_name' => 'LinkedIn Channel',
            'account_id' => 'urn:li:123',
            'access_token' => 'sec_token',
            'is_active' => true,
            'health_status' => 'healthy',
        ]);

        $this->post = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'title' => 'Feature Announcement',
            'caption' => 'Launch copy.',
            'primary_platform' => 'linkedin',
            'status' => 'approved',
        ]);
    }

    public function test_viewer_can_view_channels_but_cannot_connect_or_publish(): void
    {
        // 1. Viewer can view accounts
        $viewRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/social/accounts');
        $viewRes->assertStatus(200);

        // 2. Viewer CANNOT publish
        $pubRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/social/posts/{$this->post->id}/publish-now");
        $pubRes->assertStatus(403);

        // 3. Viewer CANNOT disconnect
        $discRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->deleteJson("/api/v1/social/accounts/{$this->account->id}");
        $discRes->assertStatus(403);
    }

    public function test_editor_cannot_publish(): void
    {
        $pubRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->editorToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/social/posts/{$this->post->id}/publish-now");
        $pubRes->assertStatus(403);
    }

    public function test_manager_can_publish_post(): void
    {
        $pubRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->managerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/social/posts/{$this->post->id}/publish-now", [
            'social_account_id' => $this->account->id,
        ]);

        $pubRes->assertStatus(200);
        $pubRes->assertJsonPath('data.status', 'published');
    }
}
