<?php

namespace App\Domains\Brand\Domain\Repositories;

use App\Domains\Brand\Application\DTOs\SaveProductServiceData;
use Illuminate\Support\Collection;

interface BrandProductServiceRepositoryInterface
{
    public function listByOrganizationId(int $organizationId): Collection;

    public function findByIdForOrganization(int $organizationId, int $id): ?object;

    public function createForOrganization(int $organizationId, int $brandProfileId, SaveProductServiceData $data): object;

    public function updateForOrganization(int $organizationId, int $id, SaveProductServiceData $data): object;

    public function deleteForOrganization(int $organizationId, int $id): bool;
}
