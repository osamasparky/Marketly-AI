<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP) Configuration
    |--------------------------------------------------------------------------
    |
    | Strict deny-by-default Content Security Policy adhering to Section 53
    | of the Marketly AI Engineering Constitution.
    |
    | Hardened rules:
    | - NO 'unsafe-eval'
    | - NO 'unsafe-inline' in production (Dynamic Nonce used for scripts)
    | - NO broad wildcard origins (https: / *)
    | - Environment-aware directives (Localhost/WS allowed ONLY in local environment)
    |
    */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'report_only' => env('CSP_REPORT_ONLY', false),
        'report_uri' => env('CSP_REPORT_URI', '/api/v1/csp-report'),

        // Configurable trusted origins (populated from environment)
        'trusted_cdn' => env('CDN_URL', ''),
        'trusted_media' => env('MEDIA_STORAGE_URL', ''),

        'base_directives' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'script-src' => [
                "'self'",
            ],
            'style-src' => [
                "'self'",
                'https://fonts.googleapis.com',
            ],
            'img-src' => [
                "'self'",
                'data:',
                'blob:',
            ],
            'font-src' => [
                "'self'",
                'https://fonts.gstatic.com',
            ],
            'connect-src' => [
                "'self'",
                'https://fonts.googleapis.com',
                'https://fonts.gstatic.com',
            ],
            'frame-src' => ["'none'"],
            'worker-src' => ["'self'", 'blob:'],
            'media-src' => ["'self'", 'blob:'],
            'manifest-src' => ["'self'"],
        ],

        // Development-only additions (strictly ignored when APP_ENV === 'production')
        'dev_additions' => [
            'style-src' => ["'unsafe-inline'"], // Needed for Vite local dev server HMR styling
            'connect-src' => [
                'http://localhost:*',
                'ws://localhost:*',
                'http://127.0.0.1:*',
                'ws://127.0.0.1:*',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Baseline Security Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    ],
];
