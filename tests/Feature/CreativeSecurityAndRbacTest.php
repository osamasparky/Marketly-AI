<?php

namespace Tests\Feature;

use App\Domains\Creative\Infrastructure\Persistence\Models\MediaAssetModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreativeSecurityAndRbacTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationModel $organization;
    private User $viewerUser;
    private User $editorUser;
    private string $viewerToken;
    private string $editorToken;
    private MediaAssetModel $asset;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->organization = OrganizationModel::create([
            'name' => 'RBAC Creative Org',
            'slug' => 'rbac-creative-org',
            'type' => 'business',
            'status' => 'active',
        ]);

        $viewerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'viewer')->first();
        $editorRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'editor')->first();

        // Viewer User
        $this->viewerUser = User::factory()->create(['email' => 'viewer-creative@marketly.test']);
        $this->organization->users()->attach($this->viewerUser->id, ['role_id' => $viewerRole->id, 'status' => 'active']);
        $this->viewerToken = $this->viewerUser->createToken('viewer-token')->plainTextToken;

        // Editor User
        $this->editorUser = User::factory()->create(['email' => 'editor-creative@marketly.test']);
        $this->organization->users()->attach($this->editorUser->id, ['role_id' => $editorRole->id, 'status' => 'active']);
        $this->editorToken = $this->editorUser->createToken('editor-token')->plainTextToken;

        $this->asset = MediaAssetModel::create([
            'organization_id' => $this->organization->id,
            'title' => 'Sample Graphic',
            'file_name' => 'sample.svg',
            'file_type' => 'graphic_card',
            'aspect_ratio' => '1:1',
            'status' => 'ready',
        ]);
    }

    public function test_viewer_can_read_assets_but_cannot_generate_or_delete(): void
    {
        // 1. Viewer can view
        $viewRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/creative/assets');
        $viewRes->assertStatus(200);

        // 2. Viewer cannot generate
        $genRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/creative/generate', [
            'title' => 'Unauthorized Generation',
        ]);
        $genRes->assertStatus(403);

        // 3. Viewer cannot delete
        $delRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->deleteJson("/api/v1/creative/assets/{$this->asset->id}");
        $delRes->assertStatus(403);
    }

    public function test_editor_can_generate_visuals(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->editorToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/creative/generate', [
            'title' => 'Editor Graphic Banner',
            'hook' => 'Created by marketing editor role.',
            'aspect_ratio' => '4:5',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Editor Graphic Banner');
    }
}
