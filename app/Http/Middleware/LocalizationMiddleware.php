<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    /**
     * Handle incoming request and negotiate the active application locale safely.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        app()->setLocale($locale);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    /**
     * Determine active locale based on explicit headers and fallback priority.
     */
    private function determineLocale(Request $request): string
    {
        $supported = config('localization.supported_locales', ['en' => [], 'ar' => []]);
        $default = config('localization.default_locale', 'en');

        // 1. Check explicit custom header 'X-Locale'
        $customHeader = $request->header('X-Locale');
        if ($customHeader && isset($supported[$customHeader])) {
            return $customHeader;
        }

        // 2. Check query parameter '?lang=' (sanitized)
        $queryLocale = $request->query('lang');
        if (is_string($queryLocale) && isset($supported[$queryLocale])) {
            return $queryLocale;
        }

        // 3. Check 'Accept-Language' header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $parsed = $this->parseAcceptLanguage($acceptLanguage, array_keys($supported));
            if ($parsed) {
                return $parsed;
            }
        }

        return $default;
    }

    /**
     * Parse and negotiate Accept-Language header safely.
     *
     * @param string $header
     * @param array<int, string> $allowed
     * @return string|null
     */
    private function parseAcceptLanguage(string $header, array $allowed): ?string
    {
        $languages = explode(',', $header);
        foreach ($languages as $lang) {
            $langCode = strtolower(trim(explode(';', $lang)[0]));
            
            // Exact match (e.g. 'ar' or 'en')
            if (in_array($langCode, $allowed, true)) {
                return $langCode;
            }

            // Prefix match (e.g. 'ar-SA' -> 'ar')
            $prefix = explode('-', $langCode)[0];
            if (in_array($prefix, $allowed, true)) {
                return $prefix;
            }
        }

        return null;
    }
}
