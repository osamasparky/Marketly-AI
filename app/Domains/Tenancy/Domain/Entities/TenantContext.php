<?php

namespace App\Domains\Tenancy\Domain\Entities;

use InvalidArgumentException;

/**
 * TenantContext represents the active isolated Organization and Brand context for a request.
 */
class TenantContext
{
    public function __construct(
        public readonly int $organizationId,
        public readonly ?int $brandId = null,
        public readonly string $role = 'viewer'
    ) {
        if ($organizationId <= 0) {
            throw new InvalidArgumentException('Organization ID must be a positive integer.');
        }
    }

    public function canMutate(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'manager', 'editor'], true);
    }

    public function canAdminister(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }
}
