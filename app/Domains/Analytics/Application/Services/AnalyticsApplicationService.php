<?php

namespace App\Domains\Analytics\Application\Services;

use App\Domains\Analytics\Domain\Services\AnalyticsIngestionService;
use App\Domains\Analytics\Domain\Services\LearningFeedbackAgent;
use App\Domains\Analytics\Domain\Services\PerformanceAttributionAgent;
use App\Domains\Analytics\Infrastructure\Persistence\Models\AiRecommendationModel;
use App\Domains\Analytics\Infrastructure\Persistence\Models\AnalyticsSnapshotModel;
use App\Domains\Analytics\Infrastructure\Persistence\Models\PostMetricModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Carbon\Carbon;

class AnalyticsApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService,
        private readonly AnalyticsIngestionService $ingestionService,
        private readonly PerformanceAttributionAgent $attributionAgent,
        private readonly LearningFeedbackAgent $feedbackAgent
    ) {}

    /**
     * Get executive analytics overview with aggregated KPIs and channel performance.
     */
    public function getOverview(TenantContext $context, array $filters = []): array
    {
        TenantIsolationGuard::assertPermission($context, 'analytics.view');

        $metricsQuery = PostMetricModel::where('organization_id', $context->organizationId);
        $snapshotsQuery = AnalyticsSnapshotModel::where('organization_id', $context->organizationId);
        $recsQuery = AiRecommendationModel::where('organization_id', $context->organizationId)->where('status', 'active');

        if ($context->brandId) {
            $metricsQuery->where('brand_profile_id', $context->brandId);
            $snapshotsQuery->where('brand_profile_id', $context->brandId);
            $recsQuery->where('brand_profile_id', $context->brandId);
        }

        $metrics = $metricsQuery->get();
        $snapshots = $snapshotsQuery->get();

        $totalReach = $metrics->sum('reach');
        $totalImpressions = $metrics->sum('views');
        $totalEngagements = $metrics->sum('likes') + $metrics->sum('comments') + $metrics->sum('shares') + $metrics->sum('clicks');
        $avgEngagementRate = $metrics->count() > 0 ? round($metrics->avg('engagement_rate'), 2) : 0.00;
        $totalClicks = $metrics->sum('clicks');
        $totalFollowers = $snapshots->sum('followers_count');

        // Channel breakdown
        $channels = ['linkedin', 'instagram', 'x', 'facebook', 'tiktok'];
        $channelStats = [];

        foreach ($channels as $channel) {
            $chQuery = PostMetricModel::where('post_metrics.organization_id', $context->organizationId)
                ->join('content_posts', 'post_metrics.content_post_id', '=', 'content_posts.id')
                ->where('content_posts.primary_platform', $channel);

            if ($context->brandId) {
                $chQuery->where('post_metrics.brand_profile_id', $context->brandId);
            }

            $chMetrics = $chQuery->select('post_metrics.*')->get();

            $channelStats[] = [
                'platform' => $channel,
                'posts_count' => $chMetrics->count(),
                'impressions' => $chMetrics->sum('views'),
                'reach' => $chMetrics->sum('reach'),
                'engagements' => $chMetrics->sum('likes') + $chMetrics->sum('comments') + $chMetrics->sum('shares'),
                'avg_engagement_rate' => $chMetrics->count() > 0 ? round($chMetrics->avg('engagement_rate'), 2) : 0.00,
            ];
        }

        return [
            'kpis' => [
                'total_reach' => $totalReach,
                'total_impressions' => $totalImpressions,
                'total_engagements' => $totalEngagements,
                'avg_engagement_rate' => $avgEngagementRate,
                'total_clicks' => $totalClicks,
                'total_followers' => $totalFollowers,
                'published_posts_count' => $metrics->count(),
            ],
            'channels' => $channelStats,
            'active_recommendations_count' => $recsQuery->count(),
        ];
    }

    /**
     * Get published posts ranked by engagement performance.
     */
    public function getContentPerformance(TenantContext $context, array $filters = []): array
    {
        TenantIsolationGuard::assertPermission($context, 'analytics.view');

        $query = PostMetricModel::with(['post.pillar', 'post.author', 'socialAccount'])
            ->where('organization_id', $context->organizationId);

        if ($context->brandId) {
            $query->where('brand_profile_id', $context->brandId);
        }

        $query->orderByDesc('engagement_rate');

        $posts = $query->paginate(15);

        return [
            'data' => $posts->items(),
            'total' => $posts->total(),
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
        ];
    }

    /**
     * Get content pillar performance and ROI rankings.
     */
    public function getPillarPerformance(TenantContext $context): array
    {
        TenantIsolationGuard::assertPermission($context, 'analytics.view');

        return $this->attributionAgent->attributePillarPerformance($context->organizationId);
    }

    /**
     * Trigger live metric sync across social channels and generate fresh AI learnings.
     */
    public function syncAnalytics(TenantContext $context): array
    {
        TenantIsolationGuard::assertPermission($context, 'analytics.view');

        $syncResult = $this->ingestionService->syncOrganizationMetrics($context->organizationId);
        $recommendations = $this->feedbackAgent->generateRecommendations($context->organizationId);

        $this->auditService->log(
            action: 'analytics.synced',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'analytics_sync',
            entityId: (string) $syncResult['posts_synced']
        );

        return [
            'sync' => $syncResult,
            'recommendations_count' => count($recommendations),
        ];
    }

    /**
     * Get active AI recommendations.
     */
    public function getRecommendations(TenantContext $context): array
    {
        TenantIsolationGuard::assertPermission($context, 'analytics.view');

        return AiRecommendationModel::with(['strategy', 'pillar'])
            ->where('organization_id', $context->organizationId)
            ->orderByDesc('confidence_score')
            ->get()
            ->toArray();
    }

    /**
     * Apply an AI recommendation.
     */
    public function applyRecommendation(TenantContext $context, int $id): AiRecommendationModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.update');

        $rec = AiRecommendationModel::where('organization_id', $context->organizationId)
            ->where('id', $id)
            ->firstOrFail();

        $rec->update([
            'status' => 'applied',
            'applied_at' => Carbon::now(),
        ]);

        $this->auditService->log(
            action: 'analytics.recommendation_applied',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'ai_recommendation',
            entityId: (string) $rec->id
        );

        return $rec;
    }

    /**
     * Dismiss an AI recommendation.
     */
    public function dismissRecommendation(TenantContext $context, int $id): AiRecommendationModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.update');

        $rec = AiRecommendationModel::where('organization_id', $context->organizationId)
            ->where('id', $id)
            ->firstOrFail();

        $rec->update([
            'status' => 'dismissed',
        ]);

        return $rec;
    }
}
