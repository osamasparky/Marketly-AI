<?php

namespace App\Domains\Analytics\Domain\Services;

use App\Domains\Analytics\Infrastructure\Persistence\Models\PostMetricModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentPillarModel;

class PerformanceAttributionAgent
{
    /**
     * Evaluate performance attribution by active content pillars.
     */
    public function attributePillarPerformance(int $organizationId): array
    {
        $pillars = ContentPillarModel::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get();

        $pillarResults = [];

        foreach ($pillars as $pillar) {
            $metrics = PostMetricModel::where('post_metrics.organization_id', $organizationId)
                ->join('content_posts', 'post_metrics.content_post_id', '=', 'content_posts.id')
                ->where('content_posts.pillar_id', $pillar->id)
                ->select(
                    'post_metrics.views',
                    'post_metrics.reach',
                    'post_metrics.likes',
                    'post_metrics.comments',
                    'post_metrics.shares',
                    'post_metrics.clicks',
                    'post_metrics.engagement_rate'
                )
                ->get();

            $postsCount = $metrics->count();
            $totalViews = $metrics->sum('views');
            $totalReach = $metrics->sum('reach');
            $totalEngagements = $metrics->sum('likes') + $metrics->sum('comments') + $metrics->sum('shares') + $metrics->sum('clicks');
            $avgEngagementRate = $postsCount > 0 ? round($metrics->avg('engagement_rate'), 2) : 0.00;

            $pillarResults[] = [
                'pillar_id' => $pillar->id,
                'pillar_name' => $pillar->name,
                'objective' => $pillar->objective,
                'target_percentage' => $pillar->recommended_percentage,
                'posts_count' => $postsCount,
                'total_views' => $totalViews,
                'total_reach' => $totalReach,
                'total_engagements' => $totalEngagements,
                'avg_engagement_rate' => $avgEngagementRate,
                'roi_score' => round($avgEngagementRate * 1.85, 1),
            ];
        }

        usort($pillarResults, fn($a, $b) => $b['avg_engagement_rate'] <=> $a['avg_engagement_rate']);

        return $pillarResults;
    }
}
