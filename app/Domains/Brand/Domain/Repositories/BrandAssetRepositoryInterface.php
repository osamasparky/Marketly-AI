<?php

namespace App\Domains\Brand\Domain\Repositories;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandAssetModel;
use Illuminate\Support\Collection;

interface BrandAssetRepositoryInterface
{
    public function listByOrganizationId(int $organizationId, ?string $type = null, ?int $brandProfileId = null): Collection;

    public function findByIdForOrganization(int $organizationId, int $id): ?BrandAssetModel;

    public function createForOrganization(int $organizationId, int $brandProfileId, array $data): BrandAssetModel;

    public function deleteForOrganization(int $organizationId, int $id): bool;
}
