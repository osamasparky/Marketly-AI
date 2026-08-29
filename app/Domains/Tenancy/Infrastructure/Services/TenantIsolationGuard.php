<?php

namespace App\Domains\Tenancy\Infrastructure\Services;

use App\Domains\Tenancy\Domain\Entities\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * TenantIsolationGuard enforces server-side tenant isolation, preventing IDOR/BOLA attacks.
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
            throw new AuthorizationException(
                "Cross-tenant access forbidden. Resource belongs to organization {$resourceTenantId}, caller is in organization {$context->organizationId}."
            );
        }
    }

    /**
     * Assert that the caller has sufficient role permissions for write/mutation operations.
     *
     * @param TenantContext $context
     * @throws AuthorizationException
     */
    public static function assertMutationPermission(TenantContext $context): void
    {
        if (!$context->canMutate()) {
            throw new AuthorizationException(
                "User with role '{$context->role}' does not have mutation permissions in organization {$context->organizationId}."
            );
        }
    }
}
