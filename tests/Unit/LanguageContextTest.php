<?php

namespace Tests\Unit;

use App\Domains\Shared\ValueObjects\LanguageContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LanguageContextTest extends TestCase
{
    public function test_valid_arabic_saudi_context(): void
    {
        $context = new LanguageContext(
            applicationLocale: 'ar',
            contentLanguage: 'ar',
            dialect: 'ar-SA',
            region: 'Saudi Arabia',
            tone: 'professional',
            audience: 'Saudi business founders'
        );

        $this->assertTrue($context->isRtl());
        $this->assertEquals('ar', $context->contentLanguage);
        $this->assertEquals('ar-SA', $context->dialect);

        $promptContext = $context->toPromptContext();
        $this->assertEquals('Arabic', $promptContext['language']);
        $this->assertEquals('ar-SA', $promptContext['dialect']);
        $this->assertTrue($promptContext['is_rtl']);
    }

    public function test_valid_english_context(): void
    {
        $context = new LanguageContext(
            applicationLocale: 'en',
            contentLanguage: 'en',
            dialect: 'en-US',
            region: 'United States',
            tone: 'casual'
        );

        $this->assertFalse($context->isRtl());
        $this->assertEquals('en', $context->contentLanguage);
        $this->assertFalse($context->toPromptContext()['is_rtl']);
    }

    public function test_unsupported_locale_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LanguageContext(applicationLocale: 'unsupported_locale');
    }

    public function test_unsupported_dialect_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LanguageContext(dialect: 'invalid_dialect_code');
    }
}
