<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales & Dialects
    |--------------------------------------------------------------------------
    |
    | Whitelisted locales and regional dialects strictly supported by Marketly AI.
    |
    */
    'default_locale' => 'en',
    'fallback_locale' => 'en',

    'supported_locales' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'direction' => 'ltr',
            'script' => 'Latn',
            'default_currency' => 'USD',
        ],
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'direction' => 'rtl',
            'script' => 'Arab',
            'default_currency' => 'SAR',
        ],
    ],

    'dialects' => [
        'ar-SA' => [
            'base' => 'ar',
            'name' => 'Saudi Arabic',
            'native' => 'اللهجة السعودية',
            'region' => 'Saudi Arabia',
        ],
        'ar-EG' => [
            'base' => 'ar',
            'name' => 'Egyptian Arabic',
            'native' => 'اللهجة المصرية',
            'region' => 'Egypt',
        ],
        'ar-AE' => [
            'base' => 'ar',
            'name' => 'Emirati Arabic',
            'native' => 'اللهجة الإماراتية',
            'region' => 'UAE',
        ],
        'en-US' => [
            'base' => 'en',
            'name' => 'American English',
            'native' => 'US English',
            'region' => 'United States',
        ],
        'en-GB' => [
            'base' => 'en',
            'name' => 'British English',
            'native' => 'UK English',
            'region' => 'United Kingdom',
        ],
    ],
];
