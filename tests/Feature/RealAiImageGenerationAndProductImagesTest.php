<?php

namespace Tests\Feature;

use App\AI\Contracts\AIProviderInterface;
use App\AI\Contracts\DTOs\AIStructuredOutput;
use App\AI\Contracts\DTOs\GenerationUsage;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProductServiceModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RealAiImageGenerationAndProductImagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OrganizationModel $organization;
    private BrandProfileModel $brandProfile;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);
        Storage::fake('public');

        $this->organization = OrganizationModel::create([
            'name' => 'Gulf Marketing Hub',
            'slug' => 'gulf-marketing-hub',
            'type' => 'business',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create(['email' => 'cmo@gulfmarketing.test']);
        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();
        $this->organization->users()->attach($this->user->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Brand Profile with locked colors
        $this->brandProfile = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Saudi Tech Horizon',
            'industry' => 'Enterprise AI',
            'primary_color' => '#10B981',
            'secondary_color' => '#064E3B',
            'accent_color' => '#34D399',
            'background_color' => '#020617',
        ]);

        BrandVoiceModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $this->brandProfile->id,
            'primary_tones' => ['authoritative', 'innovative'],
            'dialect' => 'saudi',
            'formality_scale' => 4,
        ]);
    }

    /**
     * Test Phase J: Upload and delete product images.
     */
    public function test_upload_and_delete_product_images(): void
    {
        $product = BrandProductServiceModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $this->brandProfile->id,
            'name' => 'CloudPOS Enterprise 2026',
            'type' => 'product',
            'category' => 'Fintech',
            'price' => 499,
            'currency' => 'SAR',
            'description' => 'Autonomous cloud terminal for retail chains.',
        ]);

        $imageFile1 = UploadedFile::fake()->image('pos_front.jpg', 800, 800);
        $imageFile2 = UploadedFile::fake()->image('pos_side.png', 800, 800);

        $uploadRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandProfile->id,
        ])->postJson("/api/v1/brand/products/{$product->id}/images", [
            'images' => [$imageFile1, $imageFile2],
        ]);

        $uploadRes->assertStatus(201);
        $this->assertCount(2, $uploadRes->json('data.images'));

        $assetId = $uploadRes->json('data.images.0.id');
        $this->assertDatabaseHas('brand_assets', [
            'id' => $assetId,
            'organization_id' => $this->organization->id,
            'product_service_id' => $product->id,
            'type' => 'product_image',
        ]);

        // Delete one image
        $delRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandProfile->id,
        ])->deleteJson("/api/v1/brand/products/{$product->id}/images/{$assetId}");

        $delRes->assertStatus(200);
        $this->assertDatabaseMissing('brand_assets', ['id' => $assetId]);
    }

    /**
     * Test Phase H: Real AI image generation via mock Gemini/Imagen provider.
     */
    public function test_generate_visual_with_mocked_real_ai_provider(): void
    {
        $mockProvider = $this->createMock(AIProviderInterface::class);
        $mockProvider->method('isConfigured')->willReturn(true);
        $mockProvider->method('generateImage')->willReturnCallback(function ($prompt, $options) {
            $fakePath = "creative-assets/{$options['org_id']}/{$options['brand_id']}/asset_test_1x1.jpg";
            Storage::disk('public')->put($fakePath, 'FAKE_JPEG_IMAGE_BYTES');

            return new AIStructuredOutput(
                success: true,
                data: [
                    'file_name' => 'asset_test_1x1.jpg',
                    'file_path' => $fakePath,
                    'image_url' => Storage::disk('public')->url($fakePath),
                    'mime_type' => 'image/jpeg',
                    'file_size_bytes' => 24,
                    'aspect_ratio' => $options['aspect_ratio'] ?? '1:1',
                    'mode' => 'ai_generated',
                    'prompt' => $prompt,
                ],
                usage: new GenerationUsage(latencyMs: 350)
            );
        });

        $this->app->instance(AIProviderInterface::class, $mockProvider);

        $post = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $this->brandProfile->id,
            'title' => 'Next-Gen Enterprise POS Launch',
            'hook' => 'Transform retail checkout with AI-assisted speed.',
            'caption' => 'Full announcement details...',
            'primary_platform' => 'instagram',
            'status' => 'draft',
            'visual_brief' => [
                'type' => 'product_showcase',
                'description' => 'Hero product showcase of metallic POS terminal on dark reflective surface',
                'suggested_text_overlay' => 'Speed Up 300%',
                'color_notes' => 'Emerald accent highlights with dark slate background',
            ],
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandProfile->id,
        ])->postJson('/api/v1/creative/generate', [
            'content_post_id' => $post->id,
            'aspect_ratio' => '1:1',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.file_type', 'image');
        $response->assertJsonPath('data.metadata.mode', 'ai_generated');
        $this->assertStringContainsString('Saudi Tech Horizon', $response->json('data.prompt_used'));
        $this->assertStringContainsString('LOCKED BRAND IDENTITY', $response->json('data.prompt_used'));
        $this->assertStringContainsString('VARIABLE CREATIVE COMPOSITION', $response->json('data.prompt_used'));
    }

    /**
     * Test Phase H.1: Declared SVG fallback when AI provider is offline.
     */
    public function test_generate_visual_with_declared_svg_fallback(): void
    {
        $mockProvider = $this->createMock(AIProviderInterface::class);
        $mockProvider->method('isConfigured')->willReturn(false);

        $this->app->instance(AIProviderInterface::class, $mockProvider);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandProfile->id,
        ])->postJson('/api/v1/creative/generate', [
            'title' => 'Fallback Showcase',
            'hook' => 'Modern branding banner',
            'aspect_ratio' => '1:1',
            'visual_style' => 'product_showcase',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.file_type', 'graphic_card');
        $response->assertJsonPath('data.metadata.mode', 'svg_fallback');
        $this->assertNotEmpty($response->json('data.metadata.svg_markup'));
    }
}
