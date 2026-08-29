<?php

namespace App\Domains\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * LanguageContext Value Object for multilingual AI prompts and content generation.
 */
class LanguageContext
{
    public const SUPPORTED_LOCALES = ['en', 'ar'];
    public const SUPPORTED_DIALECTS = ['ar-SA', 'ar-EG', 'ar-AE', 'en-US', 'en-GB'];

    public function __construct(
        public readonly string $applicationLocale = 'en',
        public readonly string $contentLanguage = 'en',
        public readonly ?string $dialect = null,
        public readonly ?string $region = null,
        public readonly string $tone = 'professional',
        public readonly ?string $audience = null
    ) {
        if (!in_array($this->applicationLocale, self::SUPPORTED_LOCALES, true)) {
            throw new InvalidArgumentException("Unsupported application locale '{$this->applicationLocale}'.");
        }

        if (!in_array($this->contentLanguage, self::SUPPORTED_LOCALES, true)) {
            throw new InvalidArgumentException("Unsupported content language '{$this->contentLanguage}'.");
        }

        if ($this->dialect !== null && !in_array($this->dialect, self::SUPPORTED_DIALECTS, true)) {
            throw new InvalidArgumentException("Unsupported dialect '{$this->dialect}'.");
        }
    }

    public function isRtl(): bool
    {
        return $this->contentLanguage === 'ar';
    }

    public function toPromptContext(): array
    {
        return [
            'language' => $this->contentLanguage === 'ar' ? 'Arabic' : 'English',
            'locale_code' => $this->contentLanguage,
            'dialect' => $this->dialect,
            'region' => $this->region,
            'tone' => $this->tone,
            'audience' => $this->audience,
            'is_rtl' => $this->isRtl(),
        ];
    }
}
