<?php

namespace Tests\Feature;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandBrainTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $owner;
    private OrganizationModel $org;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->owner = UserModel::factory()->create(['email' => 'founder@brand.ai']);
        $this->token = $this->owner->createToken('test')->plainTextToken;

        $this->org = OrganizationModel::create(['name' => 'Acme Labs', 'slug' => 'acme-labs']);
        $ownerRole = RoleModel::where('slug', 'owner')->first();
        $this->org->memberships()->create([
            'user_id' => $this->owner->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
        $this->owner->update(['current_organization_id' => $this->org->id]);
    }

    public function test_get_empty_brand_brain_returns_zero_completeness(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->getJson('/api/v1/brand');

        $response->assertStatus(200);
        $response->assertJsonPath('data.completeness.total_score', 0);
        $response->assertJsonPath('data.completeness.status', 'empty');
    }

    public function test_save_brand_profile_and_calculate_completeness(): void
    {
        // 1. Save Brand Profile
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson('/api/v1/brand', [
            'business_name' => 'Acme Labs',
            'industry' => 'Technology',
            'business_type' => 'B2B',
            'description' => 'Autonomous marketing AI for scale-ups and agencies.',
            'country' => 'SA',
            'tagline' => 'AI Marketing on Autopilot',
            'mission' => 'Empower businesses with autonomous marketing.',
            'vision' => 'The premier marketing employee of 2030.',
            'positioning' => 'Enterprise-grade autonomy with localized Arabic nuances.',
            'values' => ['Transparency', 'Precision', 'Innovation'],
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('data.profile.business_name', 'Acme Labs');

        // 2. Add Product/Service
        $prodRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson('/api/v1/brand/products', [
            'name' => 'Marketly Cloud Pro',
            'type' => 'product',
            'description' => 'Autonomous monthly marketing suite',
            'price' => 499.00,
            'currency' => 'SAR',
            'features' => ['Auto Strategy', 'Omni-channel Publishing'],
        ]);
        $prodRes->assertStatus(201);

        // 3. Add Target Audience
        $audRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson('/api/v1/brand/audiences', [
            'name' => 'SaaS Founders & CMOs',
            'type' => 'b2b',
            'industry' => 'Technology',
            'company_size' => '10-50 employees',
            'job_titles' => ['Founder', 'CMO', 'Growth Lead'],
            'pain_points' => ['High agency cost', 'Slow content output'],
        ]);
        $audRes->assertStatus(201);

        // 4. Set Brand Voice
        $voiceRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->patchJson('/api/v1/brand/voice', [
            'primary_tones' => ['professional', 'inspirational'],
            'formality_scale' => 3,
            'dialect' => 'saudi',
            'emoji_style' => 'moderate',
            'preferred_phrases' => ['أتمتة ذكية', 'نتائج ملموسة'],
        ]);
        $voiceRes->assertStatus(200);

        // 5. Add Goal
        $goalRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson('/api/v1/brand/goals', [
            'goal_type' => 'lead_generation',
            'priority' => 'primary',
            'description' => 'Acquire 100 qualified trial signups monthly.',
        ]);
        $goalRes->assertStatus(201);

        // 6. Verify full completeness
        $checkRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->getJson('/api/v1/brand');

        $checkRes->assertStatus(200);
        $totalScore = $checkRes->json('data.completeness.total_score');
        $this->assertGreaterThanOrEqual(80, $totalScore);
        $this->assertEquals('optimal', $checkRes->json('data.completeness.status'));
    }

    public function test_ai_context_endpoint_generates_sanitized_minimized_payload(): void
    {
        // Populate minimal profile
        $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->postJson('/api/v1/brand', [
            'business_name' => 'Acme Labs',
            'industry' => 'Technology',
            'business_type' => 'B2B',
            'description' => 'Autonomous marketing AI for scale-ups.',
            'country' => 'SA',
        ]);

        $aiRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->org->id,
        ])->getJson('/api/v1/brand/ai-context?task=content_generation&platform=linkedin');

        $aiRes->assertStatus(200);
        $aiRes->assertJsonPath('data.context.business.name', 'Acme Labs');
        $aiRes->assertJsonPath('data.context.target_platform', 'linkedin');
        $this->assertStringContainsString('<BRAND_KNOWLEDGE_BASE>', $aiRes->json('data.system_block'));
    }
}
