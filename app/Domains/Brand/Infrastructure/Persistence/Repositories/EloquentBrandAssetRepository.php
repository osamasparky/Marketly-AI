<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Repositories;

use App\Domains\Brand\Domain\Repositories\BrandAssetRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandAssetModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class EloquentBrandAssetRepository implements BrandAssetRepositoryInterface
{
    public function listByOrganizationId(int $organizationId, ?string $type = null, ?int $brandProfileId = null): Collection
    {
        $query = BrandAssetModel::where('organization_id', $organizationId);

        if ($brandProfileId) {
            $query->where('brand_profile_id', $brandProfileId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function findByIdForOrganization(int $organizationId, int $id): ?BrandAssetModel
    {
        return BrandAssetModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->first();
    }

    public function createForOrganization(int $organizationId, int $brandProfileId, array $data): BrandAssetModel
    {
        return BrandAssetModel::create([
            'organization_id' => $organizationId,
            'brand_profile_id' => $brandProfileId,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'logo',
            'file_path' => $data['file_path'],
            'mime_type' => $data['mime_type'],
            'file_size' => $data['file_size'],
            'is_public' => $data['is_public'] ?? true,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function deleteForOrganization(int $organizationId, int $id): bool
    {
        $asset = $this->findByIdForOrganization($organizationId, $id);

        if (!$asset) {
            return false;
        }

        // Delete physical file from storage disk if exists
        if (!empty($asset->file_path) && Storage::disk('public')->exists($asset->file_path)) {
            Storage::disk('public')->delete($asset->file_path);
        }

        return (bool) $asset->delete();
    }
}
