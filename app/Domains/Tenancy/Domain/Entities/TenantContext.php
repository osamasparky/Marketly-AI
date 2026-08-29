<?php

namespace App\Domains\Tenancy\Domain\Entities;

use App\Domains\Shared\Enums\UserRole;
use InvalidArgumentException;

/**
 * TenantContext represents the active isolated User, Organization, and DB-resolved Permissions.
 */
class TenantContext
{
    public readonly UserRole $userRole;

    /**
     * @param int $userId Authenticated user ID
     * @param int $organizationId Active organization tenant ID
     * @param ?int $brandId Active brand context (optional)
     * @param string|UserRole $role Active role
     * @param array<int, string> $permissions DB-backed granted permissions list
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $organizationId,
        public readonly ?int $brandId = null,
        string|UserRole $role = UserRole::VIEWER,
        public readonly array $permissions = []
    ) {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        if ($organizationId <= 0) {
            throw new InvalidArgumentException('Organization ID must be a positive integer.');
        }

        $this->userRole = is_string($role) ? (UserRole::tryFrom($role) ?? UserRole::VIEWER) : $role;
    }

    public function role(): string
    {
        return $this->userRole->value;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
