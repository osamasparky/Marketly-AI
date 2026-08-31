<?php

namespace App\Domains\Brand\Domain\Repositories;

use App\Domains\Brand\Application\DTOs\SaveBrandProfileData;

interface BrandProfileRepositoryInterface
{
    public function findByOrganizationId(int $organizationId, ?int $brandProfileId = null): ?object;

    public function findWithRelationsByOrganizationId(int $organizationId, ?int $brandProfileId = null): ?object;

    public function listByOrganizationId(int $organizationId): \Illuminate\Support\Collection;

    public function saveForOrganization(int $organizationId, SaveBrandProfileData $data, ?int $brandProfileId = null): object;

    public function ensureExistsForOrganization(int $organizationId): object;
}
