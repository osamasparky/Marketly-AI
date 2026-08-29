<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    /**
     * Handle incoming CSP violation report from browser.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $report = $request->json('csp-report') ?? $request->json();

        if (!empty($report)) {
            Log::warning('CSP Violation Detected', [
                'blocked_uri' => $report['blocked-uri'] ?? $report['blockedURL'] ?? 'unknown',
                'violated_directive' => $report['violated-directive'] ?? $report['effectiveDirective'] ?? 'unknown',
                'document_uri' => $report['document-uri'] ?? $report['documentURL'] ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return response()->json(['received' => true], 200);
    }
}
