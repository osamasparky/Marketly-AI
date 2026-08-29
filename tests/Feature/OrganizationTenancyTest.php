<?php

namespace Tests\Feature;

use App\Models\User;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_user_registration_auto_creates_default_organization_as_owner(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara Al-Otaibi',
            'email' => 'sara@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $token = $response->json('data.token');

        // Check profile
        $meRes = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/me');
        $meRes->assertStatus(200);
        $meRes->assertJsonPath('data.role', 'owner');
        $meRes->assertJsonPath('data.current_organization.name', "Sara Al-Otaibi's Workspace");
    }

    public function test_user_can_create_new_organization_and_switch(): void
    {
        $user = User::factory()->create(['email' => 'founder@acme.com']);
        $token = $user->createToken('test')->plainTextToken;

        // 1. Create Organization
        $createRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/organizations', [
                'name' => 'Acme Marketing Agency',
                'type' => 'agency',
                'default_locale' => 'ar',
            ]);

        $createRes->assertStatus(201);
        $orgId = $createRes->json('data.organization.id');

        // 2. Switch to this organization
        $switchRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/organizations/{$orgId}/switch");

        $switchRes->assertStatus(200);

        // 3. Verify /api/v1/me reflects the active organization
        $meRes = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/me');
        $meRes->assertStatus(200);
        $meRes->assertJsonPath('data.current_organization.id', $orgId);
        $meRes->assertJsonPath('data.role', 'owner');
    }

    public function test_cross_tenant_idor_is_blocked_on_organization_switch(): void
    {
        $userA = User::factory()->create(['email' => 'userA@org.com']);
        $userB = User::factory()->create(['email' => 'userB@org.com']);

        $tokenA = $userA->createToken('test')->plainTextToken;

        // Create Org belonging to User B
        $orgB = OrganizationModel::create(['name' => 'Tenant B Private Org', 'slug' => 'tenant-b']);
        $ownerRole = RoleModel::where('slug', 'owner')->first();
        $orgB->memberships()->create([
            'user_id' => $userB->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        // User A attempts to switch into Org B (Unauthorized IDOR attempt)
        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->postJson("/api/v1/organizations/{$orgB->id}/switch");

        $response->assertStatus(403);
        $this->assertEquals('You are not authorized to access this resource.', $response->json('message'));
    }

    public function test_invitation_lifecycle_and_acceptance(): void
    {
        $owner = User::factory()->create(['email' => 'owner@brand.com']);
        $ownerToken = $owner->createToken('test')->plainTextToken;

        $org = OrganizationModel::create(['name' => 'Brand Org', 'slug' => 'brand-org']);
        $ownerRole = RoleModel::where('slug', 'owner')->first();
        $org->memberships()->create([
            'user_id' => $owner->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
        $owner->update(['current_organization_id' => $org->id]);

        // 1. Owner invites editor
        $inviteRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerToken}",
            'X-Organization-Id' => (string) $org->id,
        ])->postJson("/api/v1/organizations/{$org->id}/invitations", [
            'email' => 'editor@brand.com',
            'role' => 'editor',
        ]);

        $inviteRes->assertStatus(201);
        $rawToken = $inviteRes->json('data.invitation.raw_token');

        // Reset auth guard between different user sessions in the same test
        app('auth')->forgetGuards();

        // 2. New user accepts invitation
        $editorUser = User::factory()->create(['email' => 'editor@brand.com']);
        $editorToken = $editorUser->createToken('test')->plainTextToken;

        $acceptRes = $this->withHeaders([
            'Authorization' => "Bearer {$editorToken}",
        ])->postJson('/api/v1/invitations/accept', [
            'token' => $rawToken,
        ]);

        $acceptRes->assertStatus(200);

        // 3. Verify editor has joined with editor role and permissions
        $meRes = $this->withHeaders([
            'Authorization' => "Bearer {$editorToken}",
            'X-Organization-Id' => (string) $org->id,
        ])->getJson('/api/v1/me');

        $meRes->assertStatus(200);
        $meRes->assertJsonPath('data.role', 'editor');
        $this->assertContains('content.create', $meRes->json('data.permissions'));
        $this->assertNotContains('social.publish', $meRes->json('data.permissions'));
    }

    public function test_last_owner_protection_prevents_removing_or_demoting_only_owner(): void
    {
        $owner = User::factory()->create(['email' => 'solo_owner@org.com']);
        $token = $owner->createToken('test')->plainTextToken;

        $org = OrganizationModel::create(['name' => 'Solo Org', 'slug' => 'solo-org']);
        $ownerRole = RoleModel::where('slug', 'owner')->first();
        $org->memberships()->create([
            'user_id' => $owner->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
        $owner->update(['current_organization_id' => $org->id]);

        // Attempt to demote sole owner to viewer
        $demoteRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $org->id,
        ])->patchJson("/api/v1/organizations/{$org->id}/members/{$owner->id}", [
            'role' => 'viewer',
        ]);

        $demoteRes->assertStatus(403);

        // Attempt to delete sole owner
        $deleteRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $org->id,
        ])->deleteJson("/api/v1/organizations/{$org->id}/members/{$owner->id}");

        $deleteRes->assertStatus(403);
    }
}
