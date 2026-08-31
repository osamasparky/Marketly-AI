<?php

namespace Tests\Feature;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandIdentityAndAssetsTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantAUser;
    private User $tenantBUser;
    private OrganizationModel $orgA;
    private OrganizationModel $orgB;
    private string $tokenA;
    private string $tokenB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RbacSeeder::class);

        $this->tenantAUser = User::factory()->create(['email' => 'tenantA@acme.com']);
        $this->tenantBUser = User::factory()->create(['email' => 'tenantB@competitor.com']);

        $this->orgA = OrganizationModel::create(['name' => 'Tenant A Inc', 'slug' => 'tenant-a', 'status' => 'active']);
        $this->orgB = OrganizationModel::create(['name' => 'Tenant B Inc', 'slug' => 'tenant-b', 'status' => 'active']);

        $ownerRole = DB::table('roles')->where('slug', 'owner')->first();

        $this->orgA->users()->attach($this->tenantAUser->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->orgB->users()->attach($this->tenantBUser->id, ['role_id' => $ownerRole->id, 'status' => 'active']);

        $this->tokenA = $this->tenantAUser->createToken('tokenA')->plainTextToken;
        $this->tokenB = $this->tenantBUser->createToken('tokenB')->plainTextToken;
    }

    public function test_tenant_can_save_brand_colors_and_retrieve_in_ai_context(): void
    {
        $payload = [
            'business_name' => 'Apex Marketing',
            'industry' => 'SaaS',
            'primary_color' => '#10B981',
            'secondary_color' => '#3B82F6',
            'accent_color' => '#F59E0B',
            'background_color' => '#0F172A',
            'tagline' => 'Accelerating marketing with autonomous AI',
            'mission' => 'Empower brands everywhere',
        ];

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/brand', $payload);

        $res->assertStatus(200);
        $res->assertJsonPath('data.profile.primary_color', '#10B981');
        $res->assertJsonPath('data.profile.secondary_color', '#3B82F6');
        $res->assertJsonPath('data.profile.accent_color', '#F59E0B');

        // Check AI Context
        $aiRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/brand/ai-context?task=content_generation');

        $aiRes->assertStatus(200);
        $aiRes->assertJsonPath('data.context.brand_identity.primary_color', '#10B981');
        $aiRes->assertJsonPath('data.context.brand_identity.secondary_color', '#3B82F6');
    }

    public function test_brand_colors_validation_rejects_invalid_hex(): void
    {
        $payload = [
            'business_name' => 'Apex Marketing',
            'primary_color' => 'not-a-color',
        ];

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/brand', $payload);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['primary_color']);
    }

    public function test_tenant_can_upload_logo_and_delete_asset(): void
    {
        // 1. Initial completeness check
        $initialRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/brand');
        $initialScore = $initialRes->json('data.completeness.total_score') ?? 0;

        // 2. Upload Logo
        $file = UploadedFile::fake()->image('logo.png', 400, 400);

        $uploadRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/brand/assets', [
            'file' => $file,
            'type' => 'logo',
            'name' => 'Brand Main Logo',
        ]);

        $uploadRes->assertStatus(201);
        $uploadRes->assertJsonStructure([
            'data' => [
                'asset' => ['id', 'name', 'type', 'file_path', 'public_url', 'mime_type'],
            ],
        ]);

        $assetId = $uploadRes->json('data.asset.id');
        $filePath = $uploadRes->json('data.asset.file_path');

        Storage::disk('public')->assertExists($filePath);

        // 3. Completeness score should increase with Logo added
        $afterLogoRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/brand');
        $afterScore = $afterLogoRes->json('data.completeness.total_score');
        $this->assertGreaterThanOrEqual($initialScore, $afterScore);

        // 4. List assets
        $listRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/brand/assets');
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('data.assets'));

        // 5. Delete asset
        $deleteRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->deleteJson("/api/v1/brand/assets/{$assetId}");

        $deleteRes->assertStatus(200);
        $this->assertDatabaseMissing('brand_assets', ['id' => $assetId]);
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_tenant_cannot_list_other_tenant_assets(): void
    {
        // Tenant A uploads logo
        $file = UploadedFile::fake()->image('tenant_a_logo.png', 200, 200);
        $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/brand/assets', [
            'file' => $file,
            'type' => 'logo',
        ])->assertStatus(201);

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        // Tenant B lists assets -> should only see empty array
        $tenantBList = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenB}",
            'X-Organization-Id' => (string) $this->orgB->id,
        ])->getJson('/api/v1/brand/assets');

        $tenantBList->assertStatus(200);
        $this->assertCount(0, $tenantBList->json('data.assets'));
    }

    public function test_tenant_cannot_delete_other_tenant_assets(): void
    {
        // Tenant A uploads logo
        $file = UploadedFile::fake()->image('tenant_a_logo.png', 200, 200);
        $uploadRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/brand/assets', [
            'file' => $file,
            'type' => 'logo',
        ]);
        $assetId = $uploadRes->json('data.asset.id');

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        // Tenant B attempts to delete Tenant A asset -> 404 Not Found
        $tenantBDelete = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenB}",
            'X-Organization-Id' => (string) $this->orgB->id,
        ])->deleteJson("/api/v1/brand/assets/{$assetId}");

        $tenantBDelete->assertStatus(404);

        // Tenant A asset remains intact in database
        $this->assertDatabaseHas('brand_assets', ['id' => $assetId]);
    }
}
