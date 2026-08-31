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

        if ($this->aiProvider?->isConfigured()) {
            try {
                $aiStrategy = $this->generateWithAi(
                    strategyContext: $strategyContext,
                    businessName: $businessName,
                    industry: $industry,
                    primaryObjective: $primaryObjective,
                    platforms: $platforms,
                    timeHorizon: $timeHorizon,
                    dialect: $dialect
                );

                if ($aiStrategy !== null) {
                    $this->validateStrategySchema($aiStrategy);
                    return $aiStrategy;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('MarketingStrategyGenerator AI generation failed, using fallback generator', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Deterministic Fallback Strategy
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
     * Generate marketing strategy using connected AI Provider (Gemini).
     */
    private function generateWithAi(
        array $strategyContext,
        string $businessName,
        string $industry,
        string $primaryObjective,
        array $platforms,
        int $timeHorizon,
        string $dialect
    ): ?array {
        $contextJson = json_encode($strategyContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are a CMO and chief marketing strategist for the brand "{$businessName}".
Generate a comprehensive, actionable, high-ROI marketing strategy for the next {$timeHorizon} months.

BUSINESS & BRAND INTELLIGENCE:
{$contextJson}

KEY PARAMETERS:
- Business: {$businessName}
- Industry: {$industry}
- Primary Objective: {$primaryObjective}
- Target Platforms: {$timeHorizon} months across: {$dialect} dialect

REQUIREMENTS:
1. Provide between 3 and 5 high-impact content pillars with balanced percentage distributions summing up to 100% (or 90-100%).
2. Provide 2-4 actionable campaign themes with audience persona targets.
3. Provide strategic opportunities and platform breakdowns.
4. Output MUST be valid JSON adhering strictly to the schema.
PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'primary_objective' => ['type' => 'string'],
                'secondary_objectives' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'rationale' => ['type' => 'string'],
                'pillars' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'objective' => ['type' => 'string'],
                            'priority' => ['type' => 'string'],
                            'recommended_percentage' => ['type' => 'integer']
                        ],
                        'required' => ['name', 'description', 'objective', 'priority', 'recommended_percentage']
                    ]
                ],
                'campaign_themes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'objective' => ['type' => 'string'],
                            'audience_persona' => ['type' => 'string'],
                            'core_message' => ['type' => 'string'],
                            'duration_weeks' => ['type' => 'integer'],
                            'recommended_formats' => [
                                'type' => 'array',
                                'items' => ['type' => 'string']
                            ]
                        ],
                        'required' => ['name', 'objective', 'audience_persona', 'core_message', 'duration_weeks', 'recommended_formats']
                    ]
                ],
                'opportunities' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'objective' => ['type' => 'string'],
                            'priority' => ['type' => 'string'],
                            'source' => ['type' => 'string'],
                            'recommended_timing' => ['type' => 'string']
                        ],
                        'required' => ['title', 'description', 'objective', 'priority', 'source', 'recommended_timing']
                    ]
                ],
                'platforms' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'platform' => ['type' => 'string'],
                            'primary_objective' => ['type' => 'string'],
                            'posting_frequency' => ['type' => 'string'],
                            'recommended_formats' => [
                                'type' => 'array',
                                'items' => ['type' => 'string']
                            ]
                        ],
                        'required' => ['platform', 'primary_objective', 'posting_frequency', 'recommended_formats']
                    ]
                ]
            ],
            'required' => ['name', 'description', 'primary_objective', 'secondary_objectives', 'rationale', 'pillars', 'campaign_themes', 'opportunities', 'platforms']
        ];

        $output = $this->aiProvider->generateStructured($prompt, $schema, [
            'temperature' => 0.5,
            'max_tokens' => 4096,
        ]);

        if (!$output->success || empty($output->data)) {
            return null;
        }

        $data = $output->data;

        // Ensure default fields if missing
        if (empty($data['name'])) {
            $data['name'] = "{$businessName} — Q" . ceil(date('n') / 3) . " Growth & {$primaryObjective} Strategy";
        }
        if (empty($data['description'])) {
            $data['description'] = "Quarterly marketing strategy for {$businessName}.";
        }
        if (empty($data['primary_objective'])) {
            $data['primary_objective'] = $primaryObjective;
        }
        if (empty($data['secondary_objectives']) || !is_array($data['secondary_objectives'])) {
            $data['secondary_objectives'] = ['brand_awareness', 'engagement'];
        }
        if (empty($data['rationale'])) {
            $data['rationale'] = "Strategy engineered for {$businessName} in the {$industry} sector.";
        }
        if (empty($data['pillars']) || !is_array($data['pillars'])) {
            return null;
        }

        return $data;
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
