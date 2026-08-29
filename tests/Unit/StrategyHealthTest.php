<?php

namespace Tests\Unit;

use App\Domains\Strategy\Domain\Services\StrategyHealthCalculator;
use Tests\TestCase;

class StrategyHealthTest extends TestCase
{
    public function test_health_calculation_on_null_strategy(): void
    {
        $calculator = new StrategyHealthCalculator();
        $result = $calculator->calculate(null);

        $this->assertEquals(0, $result['total_score']);
        $this->assertEquals('empty', $result['status']);
    }
}
