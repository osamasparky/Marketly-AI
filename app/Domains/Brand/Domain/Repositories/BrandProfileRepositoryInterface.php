<?php

namespace App\Domains\Brand\Domain\Repositories;

use App\Domains\Brand\Application\DTOs\SaveBrandProfileData;

interface BrandProfileRepositoryInterface
{
    public function findByOrganizationId(int $organizationId): ?object;

    public function findWithRelationsByOrganizationId(int $organizationId): ?object;

    public function saveForOrganization(int $organizationId, SaveBrandProfileData $data): object;

    public function ensureExistsForOrganization(int $organizationId): object;
}
