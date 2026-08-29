<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'status',
                    'app_name',
                    'environment',
                    'api_version',
                    'php_version',
                    'database',
                    'system_time',
                    'modules',
                ],
                'meta' => [
                    'timestamp',
                ],
            ]);

        $this->assertEquals('healthy', $response->json('data.status'));
        $this->assertEquals('v1', $response->json('data.api_version'));
    }
}
