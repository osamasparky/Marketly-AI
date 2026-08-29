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

    public function test_csp_reporting_endpoint_accepts_reports(): void
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
}
