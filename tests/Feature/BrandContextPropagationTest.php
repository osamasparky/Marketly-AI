<?php

namespace Tests\Feature;

use App\Domains\Analytics\Infrastructure\Persistence\Models\PostMetricModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\SubscriptionModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Creative\Infrastructure\Persistence\Models\MediaAssetModel;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandContextPropagationTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;
    private OrganizationModel $organization;
    private BrandProfileModel $brandA;
    private BrandProfileModel $brandB;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->organization = OrganizationModel::create([
            'name' => 'Marketly Multi-Brand Hub',
            'slug' => 'marketly-multi-brand-hub',
        ]);

        $proPlan = PlanModel::where('slug', 'pro')->first();
        SubscriptionModel::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $ownerRole = RoleModel::where('slug', 'owner')->first();
        $this->user = UserModel::factory()->create([
            'email' => 'admin@multibrand.io',
        ]);

        $this->organization->memberships()->create([
            'user_id' => $this->user->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->user->update(['current_organization_id' => $this->organization->id]);
        $this->token = $this->user->createToken('test')->plainTextToken;

        // Create Brand A and Brand B
        $this->brandA = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Brand Alpha Tech',
            'industry' => 'Technology',
            'business_type' => 'B2B',
        ]);

        $this->brandB = BrandProfileModel::create([
            'organization_id' => $this->organization->id,
            'business_name' => 'Brand Beta Fashion',
            'industry' => 'Fashion',
            'business_type' => 'B2C',
        ]);
    }

    public function test_all_domain_endpoints_strictly_isolate_brand_context(): void
    {
        // 1. Populate Brand A with Strategy
        $strategyA = MarketingStrategyModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $this->brandA->id,
            'name' => 'Q4 Alpha Growth Strategy',
            'primary_objective' => 'lead_generation',
            'target_audience' => 'CTOs and Tech Leads',
            'key_messaging' => 'Enterprise Grade AI Platform',
            'status' => 'active',
            'version' => 1,
        ]);

        // 2. Populate Brand A with Content Post
        $postA = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $this->brandA->id,
            'strategy_id' => $strategyA->id,
            'title' => 'Alpha Cloud Launch Post',
            'hook' => 'Transform your tech stack today',
            'caption' => 'Full details on our brand new cloud platform',
            'primary_platform' => 'linkedin',
            'content_type' => 'post',
            'status' => 'draft',
            'scheduled_at' => now()->addDays(2),
            'created_by' => $this->user->id,
        ]);

        // 3. Populate Brand A with Creative Asset
        $assetA = MediaAssetModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $this->brandA->id,
            'title' => 'Alpha Tech Visual Showcase',
            'file_type' => 'image_png',
            'file_path' => 'creative-assets/1/alpha.png',
            'aspect_ratio' => '1:1',
            'status' => 'ready',
            'created_by' => $this->user->id,
        ]);

        // 4. Populate Brand A with Social Account
        $accountA = SocialAccountModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $this->brandA->id,
            'platform' => 'linkedin',
            'account_id' => 'urn:li:person:alpha123',
            'account_name' => 'Alpha Tech LinkedIn Page',
            'access_token' => 'mock_token_alpha',
            'user_id' => $this->user->id,
            'is_active' => true,
            'health_status' => 'healthy',
        ]);

        // 5. Populate Brand A with Analytics Metrics
        PostMetricModel::create([
            'organization_id' => $this->organization->id,
            'brand_profile_id' => $this->brandA->id,
            'content_post_id' => $postA->id,
            'social_account_id' => $accountA->id,
            'views' => 1250,
            'reach' => 900,
            'likes' => 85,
            'comments' => 15,
            'shares' => 10,
            'clicks' => 45,
            'engagement_rate' => 12.4,
            'captured_at' => now(),
        ]);

        // --- VERIFY BRAND B CONTEXT IS EMPTY ---

        // Test 1: Strategy Endpoint with Brand B header
        $stratResB = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandB->id,
        ])->getJson('/api/v1/strategy');

        $stratResB->assertStatus(200);
        $this->assertNull($stratResB->json('data.strategy'), 'Brand B must not see Brand A strategy');

        // Test 2: Content Studio Endpoint with Brand B header
        $contentResB = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandB->id,
        ])->getJson('/api/v1/content');

        $contentResB->assertStatus(200);
        $this->assertCount(0, $contentResB->json('data'), 'Brand B must have 0 content posts');

        // Test 3: Marketing Calendar Endpoint with Brand B header
        $calendarResB = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandB->id,
        ])->getJson('/api/v1/calendar');

        $calendarResB->assertStatus(200);
        $this->assertCount(0, $calendarResB->json('data.posts'), 'Brand B calendar must not contain Brand A posts');

        // Test 4: Creative Studio Assets Endpoint with Brand B header
        $creativeResB = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandB->id,
        ])->getJson('/api/v1/creative/assets');

        $creativeResB->assertStatus(200);
        $this->assertCount(0, $creativeResB->json('data'), 'Brand B must have 0 creative assets');

        // Test 5: Social Publishing Channels Endpoint with Brand B header
        $socialResB = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandB->id,
        ])->getJson('/api/v1/social/accounts');

        $socialResB->assertStatus(200);
        $this->assertEquals(0, $socialResB->json('data.total_connected'), 'Brand B must have 0 connected social accounts');

        // Test 6: Analytics Overview Endpoint with Brand B header
        $analyticsResB = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandB->id,
        ])->getJson('/api/v1/analytics/overview');

        $analyticsResB->assertStatus(200);
        $this->assertEquals(0, $analyticsResB->json('data.kpis.total_reach'), 'Brand B total reach must be 0');
        $this->assertEquals(0, $analyticsResB->json('data.kpis.total_impressions'), 'Brand B impressions must be 0');
        $this->assertEquals(0, $analyticsResB->json('data.kpis.published_posts_count'), 'Brand B post metrics count must be 0');

        // --- VERIFY BRAND A CONTEXT ACCESSES ALL ITS DATA ---

        $contentResA = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandA->id,
        ])->getJson('/api/v1/content');

        $contentResA->assertStatus(200);
        $this->assertCount(1, $contentResA->json('data'), 'Brand A must see its own post');
        $this->assertEquals('Alpha Cloud Launch Post', $contentResA->json('data.0.title'));

        $stratResA = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandA->id,
        ])->getJson('/api/v1/strategy');

        $stratResA->assertStatus(200);
        $this->assertEquals('Q4 Alpha Growth Strategy', $stratResA->json('data.strategy.name'));

        $creativeResA = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandA->id,
        ])->getJson('/api/v1/creative/assets');

        $creativeResA->assertStatus(200);
        $this->assertCount(1, $creativeResA->json('data'));

        $socialResA = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandA->id,
        ])->getJson('/api/v1/social/accounts');

        $socialResA->assertStatus(200);
        $this->assertEquals(1, $socialResA->json('data.total_connected'));

        $analyticsResA = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
            'X-Brand-Id' => (string) $this->brandA->id,
        ])->getJson('/api/v1/analytics/overview');

        $analyticsResA->assertStatus(200);
        $this->assertEquals(900, $analyticsResA->json('data.kpis.total_reach'));
        $this->assertEquals(1250, $analyticsResA->json('data.kpis.total_impressions'));
    }
}
