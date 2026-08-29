<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_security_and_csp_headers_are_attached(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertStatus(200);

        // Verify Baseline Security Headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Verify Content-Security-Policy
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("nonce-", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
    }

    public function test_production_mode_does_not_contain_dev_origins(): void
    {
        // Simulate production environment
        $this->app['env'] = 'production';

        $response = $this->get('/api/v1/health');
        $csp = $response->headers->get('Content-Security-Policy');

        // Production must strictly NEVER contain localhost or ws: wildcards
        $this->assertStringNotContainsString('http://localhost:*', $csp);
        $this->assertStringNotContainsString('ws://localhost:*', $csp);
        $this->assertStringNotContainsString('http://127.0.0.1:*', $csp);
        $this->assertStringNotContainsString('ws://127.0.0.1:*', $csp);

        // Production style-src must NOT contain unsafe-inline
        $this->assertStringNotContainsString("style-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_csp_reporting_endpoint_accepts_valid_reports(): void
    {
        $response = $this->postJson('/api/v1/csp-report', [
            'csp-report' => [
                'document-uri' => 'http://localhost/test',
                'blocked-uri' => 'http://evil.com/script.js',
                'violated-directive' => 'script-src',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['received' => true]);
    }

    public function test_csp_reporting_endpoint_rejects_oversized_payload(): void
    {
        $largeString = str_repeat('A', 10000); // 10 KB (Limit is 8 KB)
        $response = $this->postJson('/api/v1/csp-report', [
            'csp-report' => [
                'blocked-uri' => $largeString,
            ],
        ]);

        $response->assertStatus(413);
    }

    public function test_csp_reporting_endpoint_rejects_malformed_json(): void
    {
        $response = $this->call(
            'POST',
            '/api/v1/csp-report',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{ malformed json '
        );

        $response->assertStatus(400);
    }
}
