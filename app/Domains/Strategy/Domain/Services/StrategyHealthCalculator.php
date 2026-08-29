<?php

namespace App\Domains\Strategy\Domain\Services;

use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;

class StrategyHealthCalculator
{
    /**
     * Calculate explainable Strategy Health Score (0-100%) and pillar breakdown.
     *
     * @param MarketingStrategyModel|null $strategy
     * @return array{
     *   total_score: int,
     *   status: string,
     *   pillars: array<string, array{name: string, score: int, max: int, is_complete: bool, description: string}>
     * }
     */
    public function calculate(?MarketingStrategyModel $strategy): array
    {
        if (!$strategy) {
            return [
                'total_score' => 0,
                'status' => 'empty',
                'pillars' => $this->emptyPillars(),
            ];
        }

        // 1. Objective Alignment (Max 25%)
        $objectiveScore = 0;
        if (!empty($strategy->primary_objective)) {
            $objectiveScore += 15;
        }
        if (!empty($strategy->description) || !empty($strategy->rationale)) {
            $objectiveScore += 10;
        }

        // 2. Content Pillar Mix (Max 30%)
        $pillarScore = 0;
        $pillars = $strategy->pillars;
        $pillarCount = $pillars->count();
        if ($pillarCount >= 3 && $pillarCount <= 7) {
            $pillarScore += 15;
        } elseif ($pillarCount > 0) {
            $pillarScore += 8;
        }

        $totalPercentage = $pillars->sum('recommended_percentage');
        if ($totalPercentage >= 95 && $totalPercentage <= 105) {
            $pillarScore += 15;
        } elseif ($totalPercentage > 0) {
            $pillarScore += 7;
        }

        // 3. Platform Coverage (Max 20%)
        $platformScore = 0;
        $platforms = $strategy->platforms;
        if ($platforms->count() >= 1) {
            $platformScore += 10;
        }
        if ($platforms->whereNotNull('posting_frequency')->count() >= 1) {
            $platformScore += 10;
        }

        // 4. Campaign Themes & Opportunities (Max 25%)
        $actionScore = 0;
        $themesCount = $strategy->campaignThemes->count();
        $oppsCount = $strategy->opportunities->count();
        if ($themesCount >= 1) {
            $actionScore += 12;
        }
        if ($oppsCount >= 2) {
            $actionScore += 13;
        } elseif ($oppsCount === 1) {
            $actionScore += 6;
        }

        $totalScore = $objectiveScore + $pillarScore + $platformScore + $actionScore;

        $pillarsBreakdown = [
            'objective_alignment' => [
                'name' => 'Strategic Objectives & Focus',
                'score' => $objectiveScore,
                'max' => 25,
                'is_complete' => $objectiveScore === 25,
                'description' => 'Clear primary objective and strategic rationale established.',
            ],
            'content_mix' => [
                'name' => 'Content Pillar Mix',
                'score' => $pillarScore,
                'max' => 30,
                'is_complete' => $pillarScore === 30,
                'description' => "Pillars total {$totalPercentage}% allocation across {$pillarCount} strategic themes.",
            ],
            'platform_coverage' => [
                'name' => 'Platform Strategy & Cadence',
                'score' => $platformScore,
                'max' => 20,
                'is_complete' => $platformScore === 20,
                'description' => 'Target social channels and posting cadence defined.',
            ],
            'campaigns_and_opps' => [
                'name' => 'Campaign Themes & Opportunities',
                'score' => $actionScore,
                'max' => 25,
                'is_complete' => $actionScore === 25,
                'description' => "{$themesCount} active campaign themes and {$oppsCount} content opportunities planned.",
            ],
        ];

        return [
            'total_score' => $totalScore,
            'status' => $totalScore >= 80 ? 'optimal' : ($totalScore >= 50 ? 'moderate' : 'needs_refinement'),
            'pillars' => $pillarsBreakdown,
        ];
    }

    private function emptyPillars(): array
    {
        return [
            'objective_alignment' => ['name' => 'Strategic Objectives & Focus', 'score' => 0, 'max' => 25, 'is_complete' => false, 'description' => 'No objective selected.'],
            'content_mix' => ['name' => 'Content Pillar Mix', 'score' => 0, 'max' => 30, 'is_complete' => false, 'description' => 'No content pillars created.'],
            'platform_coverage' => ['name' => 'Platform Strategy & Cadence', 'score' => 0, 'max' => 20, 'is_complete' => false, 'description' => 'No platforms configured.'],
            'campaigns_and_opps' => ['name' => 'Campaign Themes & Opportunities', 'score' => 0, 'max' => 25, 'is_complete' => false, 'description' => 'No campaigns or opportunities.'],
        ];
    }
}
