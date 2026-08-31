<?php

namespace Tests\Feature;

use App\Domains\Analytics\Infrastructure\Persistence\Models\AiRecommendationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsSecurityAndRbacTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationModel $organization;
    private User $viewerUser;
    private User $managerUser;
    private string $viewerToken;
    private string $managerToken;
    private AiRecommendationModel $recommendation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->organization = OrganizationModel::create([
            'name' => 'Analytics RBAC Lab',
            'slug' => 'an-rbac-lab',
            'type' => 'business',
            'status' => 'active',
        ]);

        $viewerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'viewer')->first();
        $managerRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'manager')->first();

        // Viewer User
        $this->viewerUser = User::factory()->create(['email' => 'viewer-analytics@marketly.test']);
        $this->organization->users()->attach($this->viewerUser->id, ['role_id' => $viewerRole->id, 'status' => 'active']);
        $this->viewerToken = $this->viewerUser->createToken('viewer-token')->plainTextToken;

        // Manager User
        $this->managerUser = User::factory()->create(['email' => 'manager-analytics@marketly.test']);
        $this->organization->users()->attach($this->managerUser->id, ['role_id' => $managerRole->id, 'status' => 'active']);
        $this->managerToken = $this->managerUser->createToken('manager-token')->plainTextToken;

        $this->recommendation = AiRecommendationModel::create([
            'organization_id' => $this->organization->id,
            'type' => 'winning_hook',
            'title' => 'Hook Optimization Opportunity',
            'explanation' => 'Actionable tip.',
            'status' => 'active',
        ]);
    }

    public function test_viewer_can_view_analytics_but_cannot_apply_recommendations(): void
    {
        // 1. Viewer can view overview
        $overviewRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->getJson('/api/v1/analytics/overview');
        $overviewRes->assertStatus(200);

        // 2. Viewer CANNOT apply recommendation
        $applyRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->viewerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/analytics/recommendations/{$this->recommendation->id}/apply");
        $applyRes->assertStatus(403);
    }

    public function test_manager_can_apply_and_dismiss_recommendation(): void
    {
        $applyRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->managerToken}",
            'X-Organization-Id' => (string) $this->organization->id,
        ])->postJson("/api/v1/analytics/recommendations/{$this->recommendation->id}/apply");

        $applyRes->assertStatus(200);
        $applyRes->assertJsonPath('data.status', 'applied');
    }
}
