<?php

namespace Tests\Unit;

use App\Domains\Strategy\Application\Services\MarketingStrategyGenerator;
use InvalidArgumentException;
use Tests\TestCase;

class MarketingStrategyGeneratorTest extends TestCase
{
    public function test_generator_synthesizes_valid_schema_strategy(): void
    {
        $generator = new MarketingStrategyGenerator();

        $context = [
            'strategic_parameters' => [
                'primary_objective' => 'lead_generation',
                'target_platforms' => ['linkedin', 'x'],
                'time_horizon_months' => 3,
            ],
            'brand_intelligence' => [
                'business_name' => 'Acme Labs',
                'industry' => 'Technology',
                'business_type' => 'B2B',
                'voice_dialect' => 'saudi',
            ],
        ];

        $output = $generator->generate($context);

        $this->assertEquals('lead_generation', $output['primary_objective']);
        $this->assertNotEmpty($output['pillars']);
        $this->assertCount(4, $output['pillars']);

        $totalPercentage = array_sum(array_column($output['pillars'], 'recommended_percentage'));
        $this->assertEquals(100, $totalPercentage);
    }
}
