<?php

namespace Tests\Unit;

use App\Domains\Brand\Domain\Services\BrandCompletenessService;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use Tests\TestCase;

class BrandCompletenessTest extends TestCase
{
    public function test_completeness_calculation_on_null_profile(): void
    {
        $service = new BrandCompletenessService();
        $result = $service->calculate(null);

        $this->assertEquals(0, $result['total_score']);
        $this->assertEquals('empty', $result['status']);
        $this->assertFalse($result['pillars']['business']['is_complete']);
    }

    public function test_completeness_calculation_on_partial_profile(): void
    {
        $service = new BrandCompletenessService();
        
        $profile = new BrandProfileModel([
            'business_name' => 'Acme Labs',
            'industry' => 'Technology',
            'business_type' => 'B2B',
            'description' => 'AI marketing platform',
        ]);

        $result = $service->calculate($profile);

        // Business score should be 20%
        $this->assertEquals(20, $result['pillars']['business']['score']);
        $this->assertTrue($result['pillars']['business']['is_complete']);
        $this->assertEquals(20, $result['total_score']);
        $this->assertEquals('incomplete', $result['status']);
    }
}
