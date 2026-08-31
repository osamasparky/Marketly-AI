<?php

namespace Tests\Feature;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrganizationAiConfigTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerUser;
    private User $otherUser;
    private OrganizationModel $organizationA;
    private OrganizationModel $organizationB;
    private string $ownerToken;
    private string $otherToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->ownerUser = User::factory()->create(['email' => 'ai-owner@brandtech.test']);
        $this->otherUser = User::factory()->create(['email' => 'ai-other@competitor.test']);

        $this->organizationA = OrganizationModel::create([
            'name' => 'BrandTech Labs',
            'slug' => 'brandtech-labs',
            'type' => 'business',
            'status' => 'active',
        ]);

        $this->organizationB = OrganizationModel::create([
            'name' => 'Other Corp',
            'slug' => 'other-corp',
            'type' => 'business',
            'status' => 'active',
        ]);

        $ownerRole = DB::table('roles')->where('slug', 'owner')->first();

        $this->organizationA->users()->attach($this->ownerUser->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->organizationB->users()->attach($this->otherUser->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->ownerUser->update(['current_organization_id' => $this->organizationA->id]);
        $this->otherUser->update(['current_organization_id' => $this->organizationB->id]);

        $this->ownerToken = $this->ownerUser->createToken('owner-token')->plainTextToken;
        $this->otherToken = $this->otherUser->createToken('other-token')->plainTextToken;
    }

    public function test_organization_owner_can_configure_byok_ai_keys_and_metadata(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->patchJson("/api/v1/organizations/{$this->organizationA->id}/ai-config", [
            'preferred_model' => 'claude-3-5-sonnet',
            'gemini_api_key' => 'AIzaSyDemoSecretGeminiKey123456',
            'openai_api_key' => 'sk-proj-superSecretOpenAiKey789',
            'anthropic_api_key' => 'sk-ant-api03-secretClaudeKey999',
            'deepseek_api_key' => 'sk-deepseek-customKey000',
            'website_url' => 'https://brandtech.io',
            'industry' => 'SaaS Marketing Tech',
            'billing_email' => 'billing@brandtech.io',
            'custom_instructions' => 'Always maintain professional yet energetic tone in all Arabic and English copy.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.organization.website_url', 'https://brandtech.io')
            ->assertJsonPath('data.organization.industry', 'SaaS Marketing Tech');

        // Fetch back and check keys are masked
        $getRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->getJson("/api/v1/organizations/{$this->organizationA->id}/ai-config");

        $getRes->assertStatus(200)
            ->assertJsonPath('data.ai_config.preferred_model', 'claude-3-5-sonnet')
            ->assertJsonPath('data.ai_config.gemini_api_key_configured', true)
            ->assertJsonPath('data.ai_config.openai_api_key_configured', true)
            ->assertJsonPath('data.ai_config.anthropic_api_key_configured', true)
            ->assertJsonPath('data.ai_config.deepseek_api_key_configured', true);

        // Secrets must be masked (should contain ellipsis '...')
        $this->assertStringContainsString('...', $getRes->json('data.ai_config.gemini_api_key_preview'));
        $this->assertStringContainsString('...', $getRes->json('data.ai_config.openai_api_key_preview'));
    }

    public function test_tenant_isolation_prevents_unauthorized_access_to_ai_keys(): void
    {
        // Other user tries to read Org A's AI config
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->otherToken}",
            'X-Organization-Id' => (string) $this->organizationA->id,
        ])->getJson("/api/v1/organizations/{$this->organizationA->id}/ai-config");

        $response->assertStatus(403);
    }
}
