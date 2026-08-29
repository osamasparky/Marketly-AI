<?php

namespace Tests\Feature;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProductServiceModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $userA;
    private OrganizationModel $orgA;
    private string $tokenA;

    private UserModel $userB;
    private OrganizationModel $orgB;
    private string $tokenB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $ownerRole = RoleModel::where('slug', 'owner')->first();

        // Tenant A
        $this->userA = UserModel::factory()->create(['email' => 'userA@orgA.com']);
        $this->tokenA = $this->userA->createToken('tokenA')->plainTextToken;
        $this->orgA = OrganizationModel::create(['name' => 'Tenant A Inc', 'slug' => 'tenant-a']);
        $this->orgA->memberships()->create(['user_id' => $this->userA->id, 'role_id' => $ownerRole->id, 'status' => 'active']);
        $this->userA->update(['current_organization_id' => $this->orgA->id]);

        // Tenant B
        $this->userB = UserModel::factory()->create(['email' => 'userB@orgB.com']);
        $this->tokenB = $this->userB->createToken('tokenB')->plainTextToken;
        $this->orgB = OrganizationModel::create(['name' => 'Tenant B Corp', 'slug' => 'tenant-b']);
        $this->orgB->memberships()->create(['user_id' => $this->userB->id, 'role_id' => $ownerRole->id, 'status' => 'active']);
        $this->userB->update(['current_organization_id' => $this->orgB->id]);
    }

    public function test_tenant_a_cannot_read_tenant_b_brand_profile(): void
    {
        // Tenant B creates brand profile
        BrandProfileModel::create([
            'organization_id' => $this->orgB->id,
            'business_name' => 'Tenant B Secret Brand',
            'industry' => 'Defense',
        ]);

        // Tenant A queries brand endpoint
        $resA = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/brand');

        $resA->assertStatus(200);
        $this->assertNull($resA->json('data.profile'));
    }

    public function test_tenant_a_cannot_update_or_delete_tenant_b_product(): void
    {
        $profileB = BrandProfileModel::create([
            'organization_id' => $this->orgB->id,
            'business_name' => 'Tenant B Brand',
        ]);

        $productB = BrandProductServiceModel::create([
            'organization_id' => $this->orgB->id,
            'brand_profile_id' => $profileB->id,
            'name' => 'Tenant B Proprietary Gadget',
            'type' => 'product',
        ]);

        // Tenant A attempts IDOR patch
        $patchRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->patchJson("/api/v1/brand/products/{$productB->id}", [
            'name' => 'Hacked Name',
        ]);

        $patchRes->assertStatus(404);

        // Tenant A attempts IDOR delete
        $deleteRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->deleteJson("/api/v1/brand/products/{$productB->id}");

        $deleteRes->assertStatus(404);

        // Verify product B was untouched
        $this->assertDatabaseHas('brand_products_services', [
            'id' => $productB->id,
            'name' => 'Tenant B Proprietary Gadget',
        ]);
    }
}
