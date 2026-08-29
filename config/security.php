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
    */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'report_only' => env('CSP_REPORT_ONLY', false),
        'report_uri' => env('CSP_REPORT_URI', '/api/v1/csp-report'),

        'directives' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'script-src' => [
                "'self'",
                // Vite HMR and local scripts via per-request cryptographic nonce
            ],
            'style-src' => [
                "'self'",
                "'unsafe-inline'", // Allowed for Tailwind utility injection; nonces applied when available
                'https://fonts.googleapis.com',
            ],
            'img-src' => [
                "'self'",
                'data:',
                'blob:',
                'https:',
            ],
            'font-src' => [
                "'self'",
                'data:',
                'https://fonts.gstatic.com',
            ],
            'connect-src' => [
                "'self'",
                'http://localhost:*',
                'ws://localhost:*',
                'https://fonts.googleapis.com',
                'https://fonts.gstatic.com',
            ],
            'frame-src' => ["'none'"],
            'worker-src' => ["'self'", 'blob:'],
            'media-src' => ["'self'", 'blob:', 'https:'],
            'manifest-src' => ["'self'"],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Security Headers Baseline
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
