<?php

namespace App\Domains\Brand\Domain\Repositories;

use App\Domains\Brand\Application\DTOs\SaveAudienceData;
use Illuminate\Support\Collection;

interface BrandAudienceRepositoryInterface
{
    public function listByOrganizationId(int $organizationId): Collection;

    public function findByIdForOrganization(int $organizationId, int $id): ?object;

    public function createForOrganization(int $organizationId, int $brandProfileId, SaveAudienceData $data): object;

    public function updateForOrganization(int $organizationId, int $id, SaveAudienceData $data): object;

    public function deleteForOrganization(int $organizationId, int $id): bool;
}
