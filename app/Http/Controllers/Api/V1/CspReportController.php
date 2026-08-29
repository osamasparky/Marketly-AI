<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    private const MAX_PAYLOAD_BYTES = 8192; // 8 KB strict max payload limit

    /**
     * Handle incoming CSP violation report from browser with strict validation and log-injection defenses.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Enforce payload size limit
        $rawContent = $request->getContent();
        if (strlen($rawContent) > self::MAX_PAYLOAD_BYTES) {
            return response()->json(['error' => 'Payload too large'], 413);
        }

        // 2. Validate JSON structure
        $decoded = json_decode($rawContent, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return response()->json(['error' => 'Invalid JSON payload'], 400);
        }

        $report = $decoded['csp-report'] ?? $decoded;

        if (is_array($report) && !empty($report)) {
            // Sanitize values to prevent log injection (newlines / control characters)
            $blockedUri = $this->sanitizeLogString($report['blocked-uri'] ?? $report['blockedURL'] ?? 'unknown');
            $violatedDirective = $this->sanitizeLogString($report['violated-directive'] ?? $report['effectiveDirective'] ?? 'unknown');
            $documentUri = $this->sanitizeLogString($report['document-uri'] ?? $report['documentURL'] ?? 'unknown');

            Log::warning('CSP Violation Detected', [
                'blocked_uri' => $blockedUri,
                'violated_directive' => $violatedDirective,
                'document_uri' => $documentUri,
                'ip' => $request->ip(),
            ]);
        }

        return response()->json(['received' => true], 200);
    }

    /**
     * Strip newlines and control characters to prevent log forgery / injection.
     */
    private function sanitizeLogString(mixed $value): string
    {
        if (!is_string($value)) {
            return 'unknown';
        }

        // Truncate to safe length and remove newline characters
        $clean = preg_replace('/[\r\n\t\x00-\x1F\x7F]/', ' ', substr($value, 0, 500));
        return trim($clean ?? '');
    }
}
