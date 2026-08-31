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

class SocialAccountTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private OrganizationModel $orgA;
    private OrganizationModel $orgB;
    private string $tokenA;
    private string $tokenB;
    private SocialAccountModel $accountA;
    private SocialAccountModel $accountB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();

        // Org A
        $this->userA = User::factory()->create(['email' => 'social-a@marketly.test']);
        $this->orgA = OrganizationModel::create(['name' => 'Social Org A', 'slug' => 'soc-org-a', 'type' => 'business', 'status' => 'active']);
        $this->orgA->users()->attach($this->userA->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenA = $this->userA->createToken('token-a')->plainTextToken;

        // Org B
        $this->userB = User::factory()->create(['email' => 'social-b@marketly.test']);
        $this->orgB = OrganizationModel::create(['name' => 'Social Org B', 'slug' => 'soc-org-b', 'type' => 'business', 'status' => 'active']);
        $this->orgB->users()->attach($this->userB->id, ['role_id' => $ownerRole->id, 'status' => 'active']);
        $this->tokenB = $this->userB->createToken('token-b')->plainTextToken;

        $this->accountA = SocialAccountModel::create([
            'organization_id' => $this->orgA->id,
            'user_id' => $this->userA->id,
            'platform' => 'linkedin',
            'account_name' => 'Tenant A LinkedIn',
            'account_id' => 'urn:li:a_123',
            'access_token' => 'token_a',
            'is_active' => true,
            'health_status' => 'healthy',
        ]);

        $this->accountB = SocialAccountModel::create([
            'organization_id' => $this->orgB->id,
            'user_id' => $this->userB->id,
            'platform' => 'linkedin',
            'account_name' => 'Tenant B LinkedIn',
            'account_id' => 'urn:li:b_456',
            'access_token' => 'token_b',
            'is_active' => true,
            'health_status' => 'healthy',
        ]);
    }

    public function test_tenant_a_cannot_disconnect_tenant_b_account(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->deleteJson("/api/v1/social/accounts/{$this->accountB->id}");

        $response->assertStatus(404);
        $this->assertTrue((bool) $this->accountB->fresh()->is_active);
    }

    public function test_tenant_a_cannot_publish_using_tenant_b_account(): void
    {
        $postA = ContentPostModel::create([
            'organization_id' => $this->orgA->id,
            'title' => 'Tenant A Post',
            'caption' => 'Trying to use Org B account.',
            'primary_platform' => 'linkedin',
            'status' => 'approved',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson("/api/v1/social/posts/{$postA->id}/publish-now", [
            'social_account_id' => $this->accountB->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_tenant_a_accounts_list_does_not_contain_tenant_b_account(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/social/accounts');

        $response->assertStatus(200);
        $response->assertJsonPath('data.total_connected', 1);
        $this->assertEquals($this->accountA->id, $response->json('data.accounts.0.id'));
    }
}
