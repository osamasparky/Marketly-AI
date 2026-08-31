<?php

namespace Tests\Feature;

use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentVariationModel;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPublishingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OrganizationModel $organization;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->user = User::factory()->create(['email' => 'publisher@marketly.test']);
        $this->organization = OrganizationModel::create([
            'name' => 'Publishing Media Corp',
            'slug' => 'publishing-media-corp',
            'type' => 'business',
            'status' => 'active',
        ]);

        $ownerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'owner')->first();
        $this->organization->users()->attach($this->user->id, [
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->token = $this->user->createToken('pub-token')->plainTextToken;

        $growthPlan = \App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel::where('slug', 'growth')->first();
        \App\Domains\Billing\Infrastructure\Persistence\Models\SubscriptionModel::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    public function test_get_connected_accounts_returns_channels_matrix(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/social/accounts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_connected',
                'channels',
                'accounts',
            ],
        ]);
        $response->assertJsonPath('data.total_connected', 0);
        $this->assertCount(5, $response->json('data.channels')); // linkedin, instagram, x, facebook, tiktok
    }

    public function test_oauth_callback_connects_social_account(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://www.linkedin.com/oauth/v2/accessToken' => \Illuminate\Support\Facades\Http::response([
                'access_token' => 'ln_live_token_123',
                'expires_in' => 5184000,
                'refresh_token' => 'ln_refresh_token_123',
            ], 200),
            'https://api.linkedin.com/v2/userinfo' => \Illuminate\Support\Facades\Http::response([
                'sub' => 'urn:li:person:auth123',
                'name' => 'Marketly Brand Admin',
                'email' => 'admin@marketly.ai',
                'picture' => 'https://example.com/avatar.png',
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson('/api/v1/social/oauth/linkedin/callback', [
            'code' => 'test_oauth_code_123',
            'callback_url' => 'http://127.0.0.1:8000/social/callback',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.platform', 'linkedin');
        $response->assertJsonPath('data.health_status', 'healthy');

        // Verify tokens are stored encrypted and NOT exposed in JSON response
        $this->assertArrayNotHasKey('access_token', $response->json('data'));
        $this->assertDatabaseHas('social_accounts', [
            'organization_id' => $this->organization->id,
            'platform' => 'linkedin',
            'health_status' => 'healthy',
        ]);
    }

    public function test_publish_now_publishes_post_and_records_job(): void
    {
        // 1. Connect LinkedIn account
        $account = SocialAccountModel::create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'platform' => 'linkedin',
            'account_name' => 'Marketly LinkedIn',
            'account_id' => 'urn:li:person:998877',
            'access_token' => 'live_encrypted_token_value',
            'is_active' => true,
            'health_status' => 'healthy',
        ]);

        // 2. Create post with variation
        $post = ContentPostModel::create([
            'organization_id' => $this->organization->id,
            'title' => 'Scaling B2B Marketing in 2026',
            'caption' => 'Strategic guide to marketing operations.',
            'primary_platform' => 'linkedin',
            'status' => 'approved',
        ]);

        ContentVariationModel::create([
            'organization_id' => $this->organization->id,
            'content_post_id' => $post->id,
            'platform' => 'linkedin',
            'format' => 'post',
            'body' => 'Professional LinkedIn version of the scaling guide.',
            'character_count' => 50,
            'status' => 'approved',
        ]);

        // 3. Publish now
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/social/posts/{$post->id}/publish-now", [
            'social_account_id' => $account->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'published');
        $this->assertNotNull($response->json('data.external_post_id'));
        $this->assertNotNull($response->json('data.external_post_url'));

        // Verify post status updated
        $this->assertEquals('published', $post->fresh()->status);
        $this->assertDatabaseHas('publishing_jobs', [
            'organization_id' => $this->organization->id,
            'content_post_id' => $post->id,
            'status' => 'published',
        ]);
    }

    public function test_health_check_and_disconnect_account(): void
    {
        $account = SocialAccountModel::create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'platform' => 'x',
            'account_name' => 'Marketly X',
            'account_id' => 'x_user_887766',
            'access_token' => 'x_encrypted_token',
            'is_active' => true,
            'health_status' => 'healthy',
        ]);

        // Health check
        $healthRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/social/accounts/{$account->id}/health-check");

        $healthRes->assertStatus(200);
        $healthRes->assertJsonPath('data.health_status', 'healthy');

        // Disconnect
        $discRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->deleteJson("/api/v1/social/accounts/{$account->id}");

        $discRes->assertStatus(200);
        $this->assertFalse((bool) $account->fresh()->is_active);
        $this->assertEquals('revoked', $account->fresh()->health_status);
    }
}
