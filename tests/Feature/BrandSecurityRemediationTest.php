<?php

namespace Tests\Feature;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandAudienceModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProductServiceModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandSecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $owner;
    private UserModel $viewer;
    private OrganizationModel $orgA;
    private OrganizationModel $orgB;
    private string $ownerToken;
    private string $viewerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->orgA = OrganizationModel::create(['name' => 'Tenant Alpha', 'slug' => 'tenant-alpha']);
        $this->orgB = OrganizationModel::create(['name' => 'Tenant Beta', 'slug' => 'tenant-beta']);

        $ownerRole = RoleModel::where('slug', 'owner')->first();
        $viewerRole = RoleModel::where('slug', 'viewer')->first();

        // User 1 - Owner of Org A
        $this->owner = UserModel::factory()->create(['email' => 'owner@alpha.com']);
        $this->orgA->memberships()->create([
            'user_id' => $this->owner->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
        $this->owner->update(['current_organization_id' => $this->orgA->id]);
        $this->ownerToken = $this->owner->createToken('owner')->plainTextToken;

        // User 2 - Viewer of Org A
        $this->viewer = UserModel::factory()->create(['email' => 'viewer@alpha.com']);
        $this->orgA->memberships()->create([
            'user_id' => $this->viewer->id,
            'role_id' => $viewerRole->id,
            'status' => 'active',
        ]);
        $this->viewer->update(['current_organization_id' => $this->orgA->id]);
        $this->viewerToken = $this->viewer->createToken('viewer')->plainTextToken;

        // Seed Org B data
        $profileB = BrandProfileModel::create([
            'organization_id' => $this->orgB->id,
            'business_name' => 'Secret Beta Business',
        ]);
        BrandProductServiceModel::create([
            'organization_id' => $this->orgB->id,
            'brand_profile_id' => $profileB->id,
            'name' => 'Beta Secret Product',
            'type' => 'product',
        ]);
        BrandAudienceModel::create([
            'organization_id' => $this->orgB->id,
            'brand_profile_id' => $profileB->id,
            'name' => 'Beta Secret Audience',
            'type' => 'b2b',
        ]);
    }

    public function test_mass_assignment_attack_cannot_override_organization_or_version(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/brand', [
            'business_name' => 'Legit Alpha Business',
            'organization_id' => $this->orgB->id, // Malicious injection
            'version' => 999,                    // Malicious version manipulation
            'status' => 'bypassed',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('brand_profiles', [
            'business_name' => 'Legit Alpha Business',
            'organization_id' => $this->orgA->id,
            'version' => 1,
        ]);

        $this->assertDatabaseMissing('brand_profiles', [
            'business_name' => 'Legit Alpha Business',
            'organization_id' => $this->orgB->id,
        ]);
    }

    public function test_viewer_role_cannot_update_brand_profile(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/brand', [
            'business_name' => 'Hacked Profile',
        ]);

        $response->assertStatus(403);
    }

    public function test_dangerous_url_schemes_are_rejected(): void
    {
        // Malicious javascript: URL
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/brand/products', [
            'name' => 'XSS Service',
            'type' => 'service',
            'url' => 'javascript:alert(document.cookie)',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);

        // Safe https URL passes
        $validResponse = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->postJson('/api/v1/brand/products', [
            'name' => 'Safe Service',
            'type' => 'service',
            'url' => 'https://marketly.ai/services/safe',
        ]);

        $validResponse->assertStatus(201);
    }

    public function test_ai_context_ignores_cross_tenant_resource_ids(): void
    {
        $productB = BrandProductServiceModel::where('organization_id', $this->orgB->id)->first();
        $audienceB = BrandAudienceModel::where('organization_id', $this->orgB->id)->first();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson("/api/v1/brand/ai-context?task=content_generation&product_id={$productB->id}&audience_id={$audienceB->id}");

        $response->assertStatus(200);
        $data = $response->json('data.context');

        // Org B's secret product and audience must NOT be leaked
        $this->assertNull($data['featured_product']);
        $this->assertNull($data['target_audience']);
        // Internal prompt template instructions must NOT be exposed
        $this->assertNull($response->json('data.system_block'));
    }

    public function test_invalid_task_parameter_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->orgA->id,
        ])->getJson('/api/v1/brand/ai-context?task=unauthorized_prompt_injection');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['task']);
    }
}
