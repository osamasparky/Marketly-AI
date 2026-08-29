<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach strict security and CSP headers.
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
            if ($header === 'Strict-Transport-Security' && !$request->isSecure() && app()->environment('local', 'testing')) {
                continue; // Skip HSTS over plain HTTP in local testing
            }
            $response->headers->set($header, $value);
        }

        // 3. Build Content-Security-Policy header
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
     * Build the CSP policy string from configuration.
     */
    private function buildCspString(string $nonce): string
    {
        $directives = config('security.csp.directives', []);
        $policyParts = [];

        foreach ($directives as $directive => $sources) {
            $sourceList = (array) $sources;

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
}
