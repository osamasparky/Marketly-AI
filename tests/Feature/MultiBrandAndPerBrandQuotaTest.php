<?php

namespace Tests\Feature;

use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\SubscriptionModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\UsageRecordModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiBrandAndPerBrandQuotaTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $owner;
    private OrganizationModel $organization;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->organization = OrganizationModel::create([
            'name' => 'OmniCorp Global',
            'slug' => 'omnicorp-global',
        ]);

        $ownerRole = RoleModel::where('slug', 'owner')->first();

        $this->owner = UserModel::factory()->create([
            'email' => 'founder@omnicorp.com',
        ]);

        $this->organization->memberships()->create([
            'user_id' => $this->owner->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->owner->update(['current_organization_id' => $this->organization->id]);
        $this->token = $this->owner->createToken('test')->plainTextToken;
    }

    public function test_starter_plan_allows_one_brand_and_blocks_second(): void
    {
        $starterPlan = PlanModel::where('slug', 'starter')->first();
        SubscriptionModel::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $starterPlan->id,
            'status' => 'active',
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        // Brand 1 creation succeeds
        $res1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/brand', [
            'business_name' => 'Brand Alpha',
            'industry' => 'Tech',
            'business_type' => 'B2B',
        ]);

        $res1->assertStatus(200);
        $this->assertDatabaseHas('brand_profiles', [
            'organization_id' => $this->organization->id,
            'business_name' => 'Brand Alpha',
        ]);

        // Brand 2 creation on Starter plan is blocked with 403
        $res2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/brand', [
            'business_name' => 'Brand Beta',
            'industry' => 'Healthcare',
            'business_type' => 'B2C',
        ]);

        $res2->assertStatus(403);
    }

    public function test_growth_plan_allows_up_to_three_brands(): void
    {
        $growthPlan = PlanModel::where('slug', 'growth')->first();
        SubscriptionModel::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        // Brand 1
        $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/brand', ['business_name' => 'Brand One'])->assertStatus(200);

        // Brand 2
        $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/brand', ['business_name' => 'Brand Two'])->assertStatus(200);

        // Brand 3
        $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/brand', ['business_name' => 'Brand Three'])->assertStatus(200);

        // Brand 4 is blocked on Growth plan
        $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/brand', ['business_name' => 'Brand Four'])->assertStatus(403);

        $this->assertEquals(3, BrandProfileModel::where('organization_id', $this->organization->id)->count());
    }

    public function test_per_brand_ai_content_quotas_are_tracked_independently(): void
    {
        $growthPlan = PlanModel::where('slug', 'growth')->first();
        SubscriptionModel::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $brandA = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Brand A',
        ]);

        $brandB = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Brand B',
        ]);

        // Max out Brand A's AI content limit (Growth gives 150 ai_content posts)
        UsageRecordModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $brandA->id,
            'feature_key' => 'ai_content',
            'used_count' => 150,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ]);

        // Generating content under Brand A fails with 403 quota exceeded
        $resA = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $brandA->id,
        ])->postJson('/api/v1/content/generate', [
            'topic' => 'AI SaaS Innovation',
            'channel' => 'linkedin',
            'intent' => 'thought_leadership',
        ]);

        $resA->assertStatus(403);

        // Generating content under Brand B SUCCEEDS because quotas are tracked per brand!
        $resB = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $brandB->id,
        ])->postJson('/api/v1/content/generate', [
            'topic' => 'Healthcare Trends 2026',
            'channel' => 'linkedin',
            'intent' => 'education',
        ]);

        $resB->assertStatus(201);

        // Brand B usage record was incremented independently
        $this->assertDatabaseHas('usage_records', [
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $brandB->id,
            'feature_key' => 'ai_content',
            'used_count' => 1,
        ]);
    }

    public function test_cross_brand_content_isolation_within_same_organization(): void
    {
        $growthPlan = PlanModel::where('slug', 'growth')->first();
        SubscriptionModel::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $brandA = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'SaaS Brand',
        ]);

        $brandB = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Ecommerce Brand',
        ]);

        // Create post for Brand A
        ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $brandA->id,
            'author_id' => $this->owner->id,
            'title' => 'Post for Brand A',
            'caption' => 'Exclusive content for Brand A',
            'primary_platform' => 'linkedin',
            'status' => 'draft',
        ]);

        // Create post for Brand B
        ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $brandB->id,
            'author_id' => $this->owner->id,
            'title' => 'Post for Brand B',
            'caption' => 'Exclusive content for Brand B',
            'primary_platform' => 'instagram',
            'status' => 'draft',
        ]);

        // Request posts scoped by Brand A
        $resA = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $brandA->id,
        ])->getJson('/api/v1/content');

        $resA->assertStatus(200);
        $postsA = $resA->json('data');
        $this->assertCount(1, $postsA);
        $this->assertEquals('Post for Brand A', $postsA[0]['title']);

        // Request posts scoped by Brand B
        $resB = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $brandB->id,
        ])->getJson('/api/v1/content');

        $resB->assertStatus(200);
        $postsB = $resB->json('data');
        $this->assertCount(1, $postsB);
        $this->assertEquals('Post for Brand B', $postsB[0]['title']);
    }

    public function test_phase_f_enriched_brand_brain_fields_persistence_and_exposure(): void
    {
        $payload = [
            'business_name' => 'FinTech Pro',
            'industry' => 'Financial Services',
            'business_type' => 'B2B',
            'preferred_platforms' => ['linkedin', 'x'],
            'content_pillars' => [
                ['name' => 'Regulatory Tech', 'description' => 'Updates on SAMA compliance'],
                ['name' => 'API Security', 'description' => 'Security standards for banking APIs'],
            ],
            'existing_social_handles' => [
                ['platform' => 'linkedin', 'handle' => 'https://linkedin.com/company/fintech-pro'],
                ['platform' => 'x', 'handle' => '@fintech_pro'],
            ],
            'approximate_monthly_budget' => 25000,
            'budget_currency' => 'SAR',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/brand', $payload);

        $response->assertStatus(200);

        $profile = BrandProfileModel::where('organization_id', $this->organization->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals(['linkedin', 'x'], $profile->preferred_platforms);
        $this->assertEquals('SAR', $profile->budget_currency);
        $this->assertEquals(25000, $profile->approximate_monthly_budget);

        // Verify AI Context endpoint returns enriched fields
        $aiCtxRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $profile->id,
        ])->getJson('/api/v1/brand/ai-context?task=content_generation');

        $aiCtxRes->assertStatus(200);
        $brandIdentity = $aiCtxRes->json('data.context.brand_identity');
        $this->assertEquals(['linkedin', 'x'], $brandIdentity['preferred_platforms']);
        $this->assertEquals(25000, $brandIdentity['approximate_monthly_budget']);
        $this->assertEquals('SAR', $brandIdentity['budget_currency']);
    }
}
