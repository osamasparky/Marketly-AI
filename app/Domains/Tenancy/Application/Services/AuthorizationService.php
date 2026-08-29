<?php

namespace App\Domains\Tenancy\Application\Services;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationMembershipModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

/**
 * AuthorizationService is the single authoritative source of truth for permissions.
 * Permissions are resolved directly from database-backed roles and memberships with request-scoped caching.
 */
class AuthorizationService
{
    /**
     * Request-scoped cache: [ "{$userId}:{$orgId}" => string[] ]
     */
    private array $permissionCache = [];

    /**
     * Determine if a user has a specific permission in an organization.
     */
    public function can(int $userId, int $organizationId, string $permission): bool
    {
        $permissions = $this->getPermissions($userId, $organizationId);
        return in_array($permission, $permissions, true);
    }

    /**
     * Authorize action or throw a generic, safe AuthorizationException with no internal state leakage.
     *
     * @throws AuthorizationException
     */
    public function authorize(int $userId, int $organizationId, string $permission): void
    {
        if (!$this->can($userId, $organizationId, $permission)) {
            Log::warning('Authorization check failed', [
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'requested_permission' => $permission,
            ]);

            throw new AuthorizationException('You are not authorized to access this resource.');
        }
    }

    /**
     * Retrieve all granted permission slugs for a user in an organization from database.
     *
     * @return array<int, string>
     */
    public function getPermissions(int $userId, int $organizationId): array
    {
        $cacheKey = "{$userId}:{$organizationId}";

        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        $membership = OrganizationMembershipModel::with('role.permissions')
            ->where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->first();

        if (!$membership || !$membership->role) {
            $this->permissionCache[$cacheKey] = [];
            return [];
        }

        $perms = $membership->role->permissions->pluck('slug')->toArray();
        $this->permissionCache[$cacheKey] = $perms;

        return $perms;
    }

    /**
     * Flush request cache.
     */
    public function flushCache(): void
    {
        $this->permissionCache = [];
    }
}
