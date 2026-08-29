<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle incoming request and apply strict, hardened security headers and CSP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Generate cryptographically secure per-request nonce
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        app()->instance('csp_nonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        // 2. Attach baseline security headers
        $headers = config('security.headers', []);
        foreach ($headers as $header => $value) {
            if ($header === 'Strict-Transport-Security') {
                if (!$request->isSecure() && !app()->environment('production')) {
                    continue; // Skip HSTS over plain HTTP in local testing
                }
            }
            $response->headers->set($header, $value);
        }

        // 3. Build hardened, environment-aware Content-Security-Policy
        if (config('security.csp.enabled', true)) {
            $csp = $this->buildCspString($nonce);
            $headerName = config('security.csp.report_only', false)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set($headerName, $csp);
        }

        return $response;
    }

    /**
     * Build the CSP policy string enforcing production lockdown and validating trusted origins.
     */
    private function buildCspString(string $nonce): string
    {
        $baseDirectives = config('security.csp.base_directives', []);
        $isDev = !app()->environment('production');
        $devAdditions = $isDev ? config('security.csp.dev_additions', []) : [];

        $cdnUrl = $this->sanitizeTrustedOrigin(config('security.csp.trusted_cdn'));
        $mediaUrl = $this->sanitizeTrustedOrigin(config('security.csp.trusted_media'));

        $policyParts = [];

        foreach ($baseDirectives as $directive => $sources) {
            $sourceList = (array) $sources;

            // In non-production only, merge dev-specific sources (e.g. Vite HMR WebSockets)
            if ($isDev && isset($devAdditions[$directive])) {
                $sourceList = array_merge($sourceList, (array) $devAdditions[$directive]);
            }

            // Inject validated trusted CDN/Storage origins into appropriate directives
            if ($cdnUrl && in_array($directive, ['script-src', 'style-src', 'font-src', 'img-src'], true)) {
                $sourceList[] = $cdnUrl;
            }
            if ($mediaUrl && in_array($directive, ['media-src', 'img-src'], true)) {
                $sourceList[] = $mediaUrl;
            }

            // Inject per-request cryptographic nonce into script-src
            if ($directive === 'script-src') {
                $sourceList[] = "'nonce-{$nonce}'";
            }

            $policyParts[] = $directive . ' ' . implode(' ', array_unique($sourceList));
        }

        if ($reportUri = config('security.csp.report_uri')) {
            $policyParts[] = 'report-uri ' . $reportUri;
        }

        return implode('; ', $policyParts);
    }

    /**
     * Sanitize and validate trusted origin to prevent wildcard / injection vulnerabilities.
     */
    private function sanitizeTrustedOrigin(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $trimmed = trim($url);

        // Disallow dangerous wildcards or relative paths
        if ($trimmed === '*' || str_starts_with($trimmed, 'https:*') || str_starts_with($trimmed, 'http:*')) {
            return null;
        }

        $parsed = parse_url($trimmed);
        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return null;
        }

        return strtolower($parsed['scheme']) . '://' . strtolower($parsed['host']) . (!empty($parsed['port']) ? ':' . $parsed['port'] : '');
    }
}
