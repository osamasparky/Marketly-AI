<?php

namespace Tests\Feature;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSecurityAndRbacTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationModel $organization;
    private User $viewerUser;
    private User $editorUser;
    private string $viewerToken;
    private string $editorToken;
    private ContentPostModel $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->organization = OrganizationModel::create([
            'name' => 'Security Test Org',
            'slug' => 'sec-org',
            'type' => 'business',
            'status' => 'active',
        ]);

        $profile = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Fintech Secure',
            'industry' => 'Financial Technology',
            'restrictions' => ['unregulated profits', '100% risk free'],
        ]);

        BrandVoiceModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $profile->id,
            'primary_tones' => ['professional'],
            'dialect' => 'saudi',
            'forbidden_phrases' => ['100% risk free', 'guaranteed returns'],
            'words_to_avoid' => ['ponzi', 'scam'],
        ]);

        $viewerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'viewer')->first();
        $editorRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'editor')->first();

        // Viewer User
        $this->viewerUser = User::factory()->create(['email' => 'viewer@marketly.test']);
        $this->organization->users()->attach($this->viewerUser->id, ['role_id' => $viewerRole->id, 'status' => 'active']);
        $this->viewerToken = $this->viewerUser->createToken('viewer-token')->plainTextToken;

        // Editor User
        $this->editorUser = User::factory()->create(['email' => 'editor@marketly.test']);
        $this->organization->users()->attach($this->editorUser->id, ['role_id' => $editorRole->id, 'status' => 'active']);
        $this->editorToken = $this->editorUser->createToken('editor-token')->plainTextToken;

        $this->post = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'title' => 'Investment Principles',
            'caption' => 'Financial planning requires discipline and risk management.',
            'primary_platform' => 'linkedin',
            'status' => 'draft',
        ]);
    }

    public function test_viewer_cannot_generate_content(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/content/generate', [
            'primary_platform' => 'linkedin',
        ]);

        $response->assertStatus(403);
    }

    public function test_viewer_cannot_update_or_delete_content(): void
    {
        $updateRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->patchJson("/api/v1/content/{$this->post->id}", [
            'title' => 'Viewer Trying to Edit',
        ]);
        $updateRes->assertStatus(403);

        $deleteRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->deleteJson("/api/v1/content/{$this->post->id}");
        $deleteRes->assertStatus(403);
    }

    public function test_editor_can_generate_and_edit_content_but_cannot_approve(): void
    {
        // 1. Editor can generate
        $genRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->editorToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/content/generate', [
            'primary_platform' => 'linkedin',
            'language' => 'en',
        ]);
        $genRes->assertStatus(201);
        $postId = $genRes->json('data.id');

        // 2. Editor can update
        $editRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->editorToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->patchJson("/api/v1/content/{$postId}", [
            'title' => 'Editor Polish Draft',
        ]);
        $editRes->assertStatus(200);

        // 3. Editor CANNOT approve (requires Manager/Admin/Owner)
        $approveRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->editorToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/content/{$postId}/approve");
        $approveRes->assertStatus(403);
    }

    public function test_content_quality_agent_flags_forbidden_vocabulary(): void
    {
        $updateRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->editorToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->patchJson("/api/v1/content/{$this->post->id}", [
            'caption' => 'Join our new scheme with 100% risk free returns and guaranteed profits today!',
        ]);

        $updateRes->assertStatus(200);
        $this->assertFalse($updateRes->json('data.latest_audit.passed_restrictions'));
        $this->assertNotEmpty($updateRes->json('data.latest_audit.warnings'));
    }
}
