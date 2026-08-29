<?php

namespace App\Domains\Strategy\Application\Services;

use App\AI\Contracts\AIProviderInterface;
use App\AI\Security\AISchemaValidator;
use InvalidArgumentException;

class MarketingStrategyGenerator
{
    public function __construct(
        private readonly ?AIProviderInterface $aiProvider = null
    ) {}

    /**
     * Generate structured AI marketing strategy draft from context.
     *
     * @param array $strategyContext
     * @return array{
     *   name: string,
     *   description: string,
     *   primary_objective: string,
     *   secondary_objectives: array<string>,
     *   rationale: string,
     *   pillars: array<array{name: string, description: string, objective: string, priority: string, recommended_percentage: int}>,
     *   campaign_themes: array<array{name: string, objective: string, audience_persona: string, core_message: string, duration_weeks: int, recommended_formats: array<string>}>,
     *   opportunities: array<array{title: string, description: string, objective: string, priority: string, source: string, recommended_timing: string}>,
     *   platforms: array<array{platform: string, primary_objective: string, posting_frequency: string, recommended_formats: array<string>}>
     * }
     */
    public function generate(array $strategyContext): array
    {
        $business = $strategyContext['brand_intelligence'] ?? [];
        $params = $strategyContext['strategic_parameters'] ?? [];

        $businessName = $business['business_name'] ?? 'The Business';
        $industry = $business['industry'] ?? 'Technology';
        $primaryObjective = $params['primary_objective'] ?? 'lead_generation';
        $platforms = $params['target_platforms'] ?? ['linkedin', 'instagram'];
        $timeHorizon = $params['time_horizon_months'] ?? 3;
        $dialect = $business['voice_dialect'] ?? 'saudi';

        // Prepare synthesized strategic response structure
        $rawStrategy = [
            'name' => "{$businessName} — Q" . ceil(date('n') / 3) . " Growth & {$primaryObjective} Strategy",
            'description' => "Autonomous quarterly marketing strategy focused on {$primaryObjective} for {$businessName} across " . implode(', ', $platforms) . ".",
            'primary_objective' => $primaryObjective,
            'secondary_objectives' => ['brand_awareness', 'engagement'],
            'rationale' => "Given {$businessName}'s positioning in the {$industry} sector with target dialect ({$dialect}), this strategy focuses on high-intent educational content and product proof to drive {$primaryObjective} over a {$timeHorizon}-month horizon.",
            'pillars' => [
                [
                    'name' => 'Educational & Industry Insights',
                    'description' => "Deep-dive actionable content addressing customer pain points in {$industry}.",
                    'objective' => 'education',
                    'priority' => 'high',
                    'recommended_percentage' => 35,
                ],
                [
                    'name' => 'Product & Value Demonstration',
                    'description' => "Showcasing core features, transformation stories, and ROI highlights.",
                    'objective' => 'sales',
                    'priority' => 'high',
                    'recommended_percentage' => 25,
                ],
                [
                    'name' => 'Social Proof & Authority',
                    'description' => 'Testimonials, client milestones, and behind-the-scenes engineering.',
                    'objective' => 'brand_awareness',
                    'priority' => 'medium',
                    'recommended_percentage' => 20,
                ],
                [
                    'name' => 'Interactive & Community Engagement',
                    'description' => 'Polls, questions, and relatable cultural/regional topics.',
                    'objective' => 'engagement',
                    'priority' => 'medium',
                    'recommended_percentage' => 20,
                ],
            ],
            'campaign_themes' => [
                [
                    'name' => "Accelerate with {$businessName}",
                    'objective' => $primaryObjective,
                    'audience_persona' => $business['target_audience']['name'] ?? 'Primary Target Audience',
                    'core_message' => "Transform your business operations with proven {$industry} expertise.",
                    'duration_weeks' => 4,
                    'recommended_formats' => ['carousel', 'short_video', 'text_post'],
                ],
            ],
            'opportunities' => [
                [
                    'title' => 'Industry Trend Analysis & Practical Takeaways',
                    'description' => "Address the latest shifts in {$industry} with localized Arabic context.",
                    'objective' => 'education',
                    'priority' => 'high',
                    'source' => 'ai_recommended',
                    'recommended_timing' => 'Week 1-2',
                ],
                [
                    'title' => 'Product Spotlight & Feature Deep-Dive',
                    'description' => 'Highlight unique selling points and customer transformation.',
                    'objective' => 'lead_generation',
                    'priority' => 'high',
                    'source' => 'ai_recommended',
                    'recommended_timing' => 'Week 3',
                ],
                [
                    'title' => 'Customer FAQ & Myth Busting',
                    'description' => 'Overcome common objections and answer key prospect questions.',
                    'objective' => 'sales',
                    'priority' => 'medium',
                    'source' => 'ai_recommended',
                    'recommended_timing' => 'Week 4',
                ],
            ],
            'platforms' => array_map(function ($platform) use ($primaryObjective) {
                return [
                    'platform' => $platform,
                    'primary_objective' => $primaryObjective,
                    'posting_frequency' => $platform === 'linkedin' ? '4x_per_week' : '5x_per_week',
                    'recommended_formats' => $platform === 'instagram' ? ['carousel', 'reel', 'story'] : ['text_post', 'article', 'carousel'],
                ];
            }, $platforms),
        ];

        // Validate strategy schema
        $this->validateStrategySchema($rawStrategy);

        return $rawStrategy;
    }

    /**
     * Validate structured AI output against business constraints.
     */
    private function validateStrategySchema(array $data): void
    {
        $schema = [
            'name' => 'string',
            'description' => 'string',
            'primary_objective' => 'string',
            'secondary_objectives' => 'array',
            'rationale' => 'string',
            'pillars' => 'array',
            'campaign_themes' => 'array',
            'opportunities' => 'array',
            'platforms' => 'array',
        ];

        AISchemaValidator::validate($data, $schema);

        // Validate percentage limits and pillar bounds
        if (count($data['pillars']) < 1 || count($data['pillars']) > 8) {
            throw new InvalidArgumentException('Strategy must contain between 1 and 8 content pillars.');
        }

        $totalPercentage = array_sum(array_column($data['pillars'], 'recommended_percentage'));
        if ($totalPercentage < 80 || $totalPercentage > 110) {
            throw new InvalidArgumentException("Content pillar mix percentage total ({$totalPercentage}%) must be between 80% and 110%.");
        }
    }
}
