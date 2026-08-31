<?php

namespace Tests\Feature;

use App\Domains\Creative\Infrastructure\Persistence\Models\MediaAssetModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreativeTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private OrganizationModel $orgA;
    private OrganizationModel $orgB;
    private string $tokenA;
    private string $tokenB;
    private MediaAssetModel $assetA;
    private MediaAssetModel $assetB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();

        // Org A
        $this->userA = User::factory()->create(['email' => 'user-a@marketly.test']);
        $this->orgA = OrganizationModel::create(['name' => 'Agency A', 'slug' => 'agency-a', 'type' => 'business', 'status' => 'active']);
        $this->orgA->users()->attach($this->userA->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenA = $this->userA->createToken('token-a')->plainTextToken;

        // Org B
        $this->userB = User::factory()->create(['email' => 'user-b@marketly.test']);
        $this->orgB = OrganizationModel::create(['name' => 'Agency B', 'slug' => 'agency-b', 'type' => 'business', 'status' => 'active']);
        $this->orgB->users()->attach($this->userB->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenB = $this->userB->createToken('token-b')->plainTextToken;

        $this->assetA = MediaAssetModel::create([
            'organization_id' => $this->orgA->id,
            'title' => 'Secret Asset for Tenant A',
            'file_name' => 'secret_a.svg',
            'file_type' => 'graphic_card',
            'aspect_ratio' => '1:1',
            'status' => 'ready',
        ]);

        $this->assetB = MediaAssetModel::create([
            'organization_id' => $this->orgB->id,
            'title' => 'Proprietary Asset for Tenant B',
            'file_name' => 'secret_b.svg',
            'file_type' => 'graphic_card',
            'aspect_ratio' => '4:5',
            'status' => 'ready',
        ]);
    }

    public function test_tenant_a_cannot_read_tenant_b_asset(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson("/api/v1/creative/assets/{$this->assetB->id}");

        $response->assertStatus(404);
    }

    public function test_tenant_a_cannot_delete_tenant_b_asset(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->deleteJson("/api/v1/creative/assets/{$this->assetB->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('media_assets', ['id' => $this->assetB->id]);
    }

    public function test_tenant_a_assets_list_does_not_contain_tenant_b_assets(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/creative/assets');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $this->assetA->id);
    }
}
