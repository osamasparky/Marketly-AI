<?php

namespace App\Domains\Tenancy\Domain\Entities;

use App\Domains\Shared\Enums\UserRole;
use InvalidArgumentException;

/**
 * TenantContext represents the active isolated Organization and Brand context for a request.
 */
class TenantContext
{
    public readonly UserRole $userRole;

    public function __construct(
        public readonly int $organizationId,
        public readonly ?int $brandId = null,
        string|UserRole $role = UserRole::VIEWER
    ) {
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
        return $this->userRole->hasPermission($permission);
    }
}
