<?php

namespace Tests\Feature;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private OrganizationModel $orgA;
    private OrganizationModel $orgB;
    private string $tokenA;
    private string $tokenB;
    private ContentPostModel $postA;
    private ContentPostModel $postB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();

        // Organization A
        $this->userA = User::factory()->create(['email' => 'user-a@marketly.test']);
        $this->orgA = OrganizationModel::create(['name' => 'Org A', 'slug' => 'org-a', 'type' => 'business', 'status' => 'active']);
        $this->orgA->users()->attach($this->userA->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenA = $this->userA->createToken('token-a')->plainTextToken;

        // Organization B
        $this->userB = User::factory()->create(['email' => 'user-b@marketly.test']);
        $this->orgB = OrganizationModel::create(['name' => 'Org B', 'slug' => 'org-b', 'type' => 'business', 'status' => 'active']);
        $this->orgB->users()->attach($this->userB->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenB = $this->userB->createToken('token-b')->plainTextToken;

        // Posts
        $this->postA = ContentPostModel::create([
            'organization_id' => $this->orgA->id,
            'title' => 'Secret Growth Plan for Tenant A',
            'caption' => 'Confidential campaign strategy for Tenant A clients.',
            'primary_platform' => 'linkedin',
            'status' => 'draft',
        ]);

        $this->postB = ContentPostModel::create([
            'organization_id' => $this->orgB->id,
            'title' => 'Confidential Launch for Tenant B',
            'caption' => 'Proprietary product launch copy for Tenant B customers.',
            'primary_platform' => 'instagram',
            'status' => 'draft',
        ]);
    }

    public function test_tenant_a_cannot_read_tenant_b_post(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson("/api/v1/content/{$this->postB->id}");

        $response->assertStatus(404);
    }

    public function test_tenant_a_cannot_update_tenant_b_post(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->patchJson("/api/v1/content/{$this->postB->id}", [
            'title' => 'Malicious Hack Overwrite',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('Confidential Launch for Tenant B', $this->postB->fresh()->title);
    }

    public function test_tenant_a_cannot_delete_tenant_b_post(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->deleteJson("/api/v1/content/{$this->postB->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('content_posts', ['id' => $this->postB->id]);
    }

    public function test_tenant_a_cannot_approve_tenant_b_post(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson("/api/v1/content/{$this->postB->id}/approve");

        $response->assertStatus(404);
        $this->assertEquals('draft', $this->postB->fresh()->status);
    }

    public function test_tenant_a_posts_list_does_not_contain_tenant_b_posts(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/content');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $this->postA->id);
    }
}
