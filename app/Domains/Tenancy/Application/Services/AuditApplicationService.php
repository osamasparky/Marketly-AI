<?php

namespace App\Domains\Tenancy\Application\Services;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\AuditLogModel;
use Illuminate\Support\Facades\Log;

class AuditApplicationService
{
    /**
     * Record an append-only audit event with sanitized metadata.
     */
    public function log(
        string $action,
        ?int $organizationId = null,
        ?int $userId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        array $metadata = []
    ): void {
        // Strip any sensitive keys from metadata
        $safeMetadata = $this->sanitizeMetadata($metadata);

        try {
            AuditLogModel::create([
                'organization_id' => $organizationId,
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metadata_json' => $safeMetadata,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging failure crash business transaction; fallback to system log
            Log::error("Failed to write database audit log: {$e->getMessage()}", [
                'action' => $action,
                'organization_id' => $organizationId,
                'user_id' => $userId,
            ]);
        }
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $forbiddenKeys = [
            'password', 'password_confirmation', 'token', 'access_token', 'refresh_token',
            'api_key', 'secret', 'gemini_api_key', 'authorization', 'cookie',
        ];

        $clean = [];
        foreach ($metadata as $key => $value) {
            if (in_array(strtolower((string)$key), $forbiddenKeys, true)) {
                $clean[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $clean[$key] = $this->sanitizeMetadata($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
