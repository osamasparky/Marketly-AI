<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Repositories;

use App\Domains\Brand\Application\DTOs\SaveCompetitorData;
use App\Domains\Brand\Domain\Repositories\BrandCompetitorRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandCompetitorModel;
use Illuminate\Support\Collection;

class EloquentBrandCompetitorRepository implements BrandCompetitorRepositoryInterface
{
    public function listByOrganizationId(int $organizationId): Collection
    {
        return BrandCompetitorModel::where('organization_id', $organizationId)->get();
    }

    public function findByIdForOrganization(int $organizationId, int $id): ?BrandCompetitorModel
    {
        return BrandCompetitorModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->first();
    }

    public function createForOrganization(int $organizationId, int $brandProfileId, SaveCompetitorData $data): BrandCompetitorModel
    {
        return BrandCompetitorModel::create([
            'organization_id' => $organizationId,
            'brand_profile_id' => $brandProfileId,
            'name' => $data->name,
            'website' => $data->website,
            'description' => $data->description,
            'positioning' => $data->positioning,
            'strengths' => $data->strengths,
            'weaknesses' => $data->weaknesses,
            'notes' => $data->notes,
        ]);
    }

    public function updateForOrganization(int $organizationId, int $id, SaveCompetitorData $data): BrandCompetitorModel
    {
        $model = BrandCompetitorModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        $updateData = [];
        if ($data->name !== '') {
            $updateData['name'] = $data->name;
        }
        if ($data->website !== null) {
            $updateData['website'] = $data->website;
        }
        if ($data->description !== null) {
            $updateData['description'] = $data->description;
        }
        if ($data->positioning !== null) {
            $updateData['positioning'] = $data->positioning;
        }
        if (!empty($data->strengths)) {
            $updateData['strengths'] = $data->strengths;
        }
        if (!empty($data->weaknesses)) {
            $updateData['weaknesses'] = $data->weaknesses;
        }
        if ($data->notes !== null) {
            $updateData['notes'] = $data->notes;
        }

        if (!empty($updateData)) {
            $model->update($updateData);
        }

        return $model;
    }

    public function deleteForOrganization(int $organizationId, int $id): bool
    {
        $model = BrandCompetitorModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        return (bool) $model->delete();
    }
}
