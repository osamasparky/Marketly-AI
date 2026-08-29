<?php

namespace App\Domains\Brand\Domain\Repositories;

use App\Domains\Brand\Application\DTOs\SaveCompetitorData;
use Illuminate\Support\Collection;

interface BrandCompetitorRepositoryInterface
{
    public function listByOrganizationId(int $organizationId): Collection;

    public function findByIdForOrganization(int $organizationId, int $id): ?object;

    public function createForOrganization(int $organizationId, int $brandProfileId, SaveCompetitorData $data): object;

    public function updateForOrganization(int $organizationId, int $id, SaveCompetitorData $data): object;

    public function deleteForOrganization(int $organizationId, int $id): bool;
}
