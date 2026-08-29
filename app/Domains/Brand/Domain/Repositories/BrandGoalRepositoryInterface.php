<?php

namespace App\Domains\Brand\Domain\Repositories;

use App\Domains\Brand\Application\DTOs\SaveGoalData;
use Illuminate\Support\Collection;

interface BrandGoalRepositoryInterface
{
    public function listByOrganizationId(int $organizationId): Collection;

    public function findByIdForOrganization(int $organizationId, int $id): ?object;

    public function createForOrganization(int $organizationId, int $brandProfileId, SaveGoalData $data): object;

    public function updateForOrganization(int $organizationId, int $id, SaveGoalData $data): object;

    public function deleteForOrganization(int $organizationId, int $id): bool;
}
