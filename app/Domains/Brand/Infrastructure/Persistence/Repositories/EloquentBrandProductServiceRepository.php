<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Repositories;

use App\Domains\Brand\Application\DTOs\SaveProductServiceData;
use App\Domains\Brand\Domain\Repositories\BrandProductServiceRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProductServiceModel;
use Illuminate\Support\Collection;

class EloquentBrandProductServiceRepository implements BrandProductServiceRepositoryInterface
{
    public function listByOrganizationId(int $organizationId): Collection
    {
        return BrandProductServiceModel::where('organization_id', $organizationId)->get();
    }

    public function findByIdForOrganization(int $organizationId, int $id): ?BrandProductServiceModel
    {
        return BrandProductServiceModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->first();
    }

    public function createForOrganization(int $organizationId, int $brandProfileId, SaveProductServiceData $data): BrandProductServiceModel
    {
        return BrandProductServiceModel::create([
            'organization_id' => $organizationId,
            'brand_profile_id' => $brandProfileId,
            'name' => $data->name,
            'type' => $data->type,
            'description' => $data->description,
            'category' => $data->category,
            'price' => $data->price,
            'currency' => $data->currency,
            'url' => $data->url,
            'features' => $data->features,
            'status' => 'active',
        ]);
    }

    public function updateForOrganization(int $organizationId, int $id, SaveProductServiceData $data): BrandProductServiceModel
    {
        $model = BrandProductServiceModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        $updateData = [];
        if ($data->name !== '') {
            $updateData['name'] = $data->name;
        }
        if ($data->type !== '') {
            $updateData['type'] = $data->type;
        }
        if ($data->description !== null) {
            $updateData['description'] = $data->description;
        }
        if ($data->category !== null) {
            $updateData['category'] = $data->category;
        }
        if ($data->price !== null) {
            $updateData['price'] = $data->price;
        }
        if ($data->currency !== '') {
            $updateData['currency'] = $data->currency;
        }
        if ($data->url !== null) {
            $updateData['url'] = $data->url;
        }
        if (!empty($data->features)) {
            $updateData['features'] = $data->features;
        }

        if (!empty($updateData)) {
            $model->update($updateData);
        }

        return $model;
    }

    public function deleteForOrganization(int $organizationId, int $id): bool
    {
        $model = BrandProductServiceModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        return (bool) $model->delete();
    }
}
