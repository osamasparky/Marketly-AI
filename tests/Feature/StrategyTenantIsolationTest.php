<?php

namespace Tests\Feature;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrategyTenantIsolationTest extends TestCase
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

    public function test_tenant_a_cannot_read_tenant_b_strategy(): void
    {
        // Tenant B creates active strategy
        MarketingStrategyModel::create([
            'organization_id' => $this->orgB->id,
            'name' => 'Tenant B Confidential Strategy',
            'primary_objective' => 'sales',
            'status' => 'active',
        ]);

        // Tenant A queries strategy
        $resA = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/strategy');

        $resA->assertStatus(200);
        $this->assertNull($resA->json('data.strategy'));
    }

    public function test_tenant_a_cannot_update_or_activate_tenant_b_strategy(): void
    {
        $stratB = MarketingStrategyModel::create([
            'organization_id' => $this->orgB->id,
            'name' => 'Tenant B Strategy',
            'primary_objective' => 'sales',
            'status' => 'draft',
        ]);

        // Tenant A attempts IDOR patch
        $patchRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->patchJson("/api/v1/strategy/{$stratB->id}", [
            'name' => 'Hijacked Strategy',
        ]);
        $patchRes->assertStatus(404);

        // Tenant A attempts IDOR activate
        $actRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson("/api/v1/strategy/{$stratB->id}/activate");
        $actRes->assertStatus(404);

        // Verify Strategy B remains draft
        $this->assertEquals('draft', $stratB->fresh()->status);
        $this->assertEquals('Tenant B Strategy', $stratB->fresh()->name);
    }
}
