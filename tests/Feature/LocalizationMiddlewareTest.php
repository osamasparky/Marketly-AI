<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalizationMiddlewareTest extends TestCase
{
    public function test_custom_header_x_locale_sets_arabic(): void
    {
        $response = $this->getJson('/api/v1/health', [
            'X-Locale' => 'ar',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Language', 'ar');
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_accept_language_header_negotiates_arabic(): void
    {
        $response = $this->getJson('/api/v1/health', [
            'Accept-Language' => 'ar-EG,ar;q=0.9,en;q=0.8',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Language', 'ar');
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_unsupported_language_falls_back_to_default_english(): void
    {
        $response = $this->getJson('/api/v1/health', [
            'Accept-Language' => 'zh-CN,zh;q=0.9',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Language', 'en');
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_query_parameter_lang_sets_arabic(): void
    {
        $response = $this->getJson('/api/v1/health?lang=ar');

        $response->assertStatus(200);
        $response->assertHeader('Content-Language', 'ar');
        $this->assertEquals('ar', app()->getLocale());
    }
}
