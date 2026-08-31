<?php

namespace Tests\Feature;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Creative\Infrastructure\Persistence\Models\MediaAssetModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreativeStudioLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OrganizationModel $organization;
    private string $token;
    private ContentPostModel $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->user = User::factory()->create(['email' => 'designer@marketly.test']);
        $this->organization = OrganizationModel::create([
            'name' => 'Design Studio Co',
            'slug' => 'design-studio-co',
            'type' => 'business',
            'status' => 'active',
        ]);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();
        $this->organization->users()->attach($this->user->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Brand Profile & Voice setup
        $profile = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Apex Agency',
            'industry' => 'Creative Design',
        ]);

        BrandVoiceModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $profile->id,
            'primary_tones' => ['professional', 'witty'],
            'dialect' => 'saudi',
            'forbidden_phrases' => ['cheap design'],
        ]);

        $this->post = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'title' => 'Scaling Social Reach 2026',
            'hook' => 'Stop relying on organic luck — build systematic branded creatives.',
            'caption' => 'Here are 3 fundamental principles for modern visual marketing.',
            'primary_platform' => 'instagram',
            'status' => 'draft',
        ]);
    }

    public function test_get_empty_assets_list(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/creative/assets');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_generate_visual_asset_across_different_aspect_ratios(): void
    {
        $ratios = ['1:1', '4:5', '9:16', '16:9'];

        foreach ($ratios as $ratio) {
            $response = $this->withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'X-Organization-Id' => (string) $this->organization->id,
            ])->postJson('/api/v1/creative/generate', [
                'content_post_id' => $this->post->id,
                'visual_style' => 'branded_quote',
                'aspect_ratio' => $ratio,
            ]);

            $response->assertStatus(201);
            $response->assertJsonPath('data.aspect_ratio', $ratio);
            $response->assertJsonPath('data.file_type', 'graphic_card');
            $this->assertNotEmpty($response->json('data.metadata.svg_markup'));

            $assetId = $response->json('data.id');
            $this->assertDatabaseHas('media_assets', [
                'id' => $assetId,
                'organization_id' => $this->organization->id,
                'aspect_ratio' => $ratio,
            ]);
        }
    }

    public function test_generate_video_reel_script(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/creative/generate-reel', [
            'content_post_id' => $this->post->id,
            'dialect' => 'saudi',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.file_type', 'video_script');
        $response->assertJsonPath('data.aspect_ratio', '9:16');
        $this->assertNotEmpty($response->json('data.metadata.scenes'));
        $this->assertCount(4, $response->json('data.metadata.scenes'));
    }

    public function test_attach_asset_to_post_and_delete(): void
    {
        // 1. Generate standalone asset
        $genRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/creative/generate', [
            'title' => 'Standalone Banner',
            'hook' => 'Instant visual design hook',
            'aspect_ratio' => '1:1',
        ]);

        $genRes->assertStatus(201);
        $assetId = $genRes->json('data.id');
        $this->assertNull($genRes->json('data.content_post_id'));

        // 2. Attach to post
        $attachRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/creative/assets/{$assetId}/attach", [
            'content_post_id' => $this->post->id,
        ]);

        $attachRes->assertStatus(200);
        $attachRes->assertJsonPath('data.content_post_id', $this->post->id);

        // 3. Delete asset
        $delRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->deleteJson("/api/v1/creative/assets/{$assetId}");

        $delRes->assertStatus(200);
        $this->assertDatabaseMissing('media_assets', ['id' => $assetId]);
    }
}
