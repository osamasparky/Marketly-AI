<?php

namespace App\Domains\Tenancy\Infrastructure\Services;

use App\Domains\Tenancy\Application\Services\AuthorizationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

/**
 * TenantIsolationGuard enforces server-side tenant isolation and permission defense.
 */
class TenantIsolationGuard
{
    /**
     * Assert that the requested resource belongs strictly to the authenticated tenant.
     *
     * @param int $resourceTenantId Organization ID of the resource
     * @param TenantContext $context Active tenant context from server-side session/token
     * @throws AuthorizationException
     */
    public static function assertTenantAccess(int $resourceTenantId, TenantContext $context): void
    {
        if ($resourceTenantId !== $context->organizationId) {
            // Write detailed correlation info to secure server logs ONLY
            Log::warning('Cross-tenant IDOR access attempt blocked', [
                'resource_tenant_id' => $resourceTenantId,
                'caller_tenant_id' => $context->organizationId,
                'user_id' => $context->userId,
                'role' => $context->role(),
            ]);

            // User-facing error message must remain strictly generic
            throw new AuthorizationException('You are not authorized to access this resource.');
        }
    }

    /**
     * Assert that the caller has sufficient role permissions for a specific granular permission.
     *
     * @param TenantContext $context
     * @param string $permission
     * @throws AuthorizationException
     */
    public static function assertPermission(TenantContext $context, string $permission): void
    {
        $authService = app(AuthorizationService::class);
        $authService->authorize($context->userId, $context->organizationId, $permission);
    }
}
