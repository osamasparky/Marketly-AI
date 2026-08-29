<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Repositories;

use App\Domains\Brand\Application\DTOs\SaveAudienceData;
use App\Domains\Brand\Domain\Repositories\BrandAudienceRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandAudienceModel;
use Illuminate\Support\Collection;

class EloquentBrandAudienceRepository implements BrandAudienceRepositoryInterface
{
    public function listByOrganizationId(int $organizationId): Collection
    {
        return BrandAudienceModel::where('organization_id', $organizationId)->get();
    }

    public function findByIdForOrganization(int $organizationId, int $id): ?BrandAudienceModel
    {
        return BrandAudienceModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->first();
    }

    public function createForOrganization(int $organizationId, int $brandProfileId, SaveAudienceData $data): BrandAudienceModel
    {
        return BrandAudienceModel::create([
            'organization_id' => $organizationId,
            'brand_profile_id' => $brandProfileId,
            'name' => $data->name,
            'type' => $data->type,
            'description' => $data->description,
            'age_range' => $data->ageRange,
            'gender' => $data->gender,
            'locations' => $data->locations,
            'interests' => $data->interests,
            'pain_points' => $data->painPoints,
            'needs' => $data->needs,
            'industry' => $data->industry,
            'company_size' => $data->companySize,
            'job_titles' => $data->jobTitles,
            'status' => 'active',
        ]);
    }

    public function updateForOrganization(int $organizationId, int $id, SaveAudienceData $data): BrandAudienceModel
    {
        $model = BrandAudienceModel::where('organization_id', $organizationId)
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
        if ($data->ageRange !== null) {
            $updateData['age_range'] = $data->ageRange;
        }
        if ($data->gender !== '') {
            $updateData['gender'] = $data->gender;
        }
        if (!empty($data->locations)) {
            $updateData['locations'] = $data->locations;
        }
        if (!empty($data->interests)) {
            $updateData['interests'] = $data->interests;
        }
        if (!empty($data->painPoints)) {
            $updateData['pain_points'] = $data->painPoints;
        }
        if (!empty($data->needs)) {
            $updateData['needs'] = $data->needs;
        }
        if ($data->industry !== null) {
            $updateData['industry'] = $data->industry;
        }
        if ($data->companySize !== null) {
            $updateData['company_size'] = $data->companySize;
        }
        if (!empty($data->jobTitles)) {
            $updateData['job_titles'] = $data->jobTitles;
        }

        if (!empty($updateData)) {
            $model->update($updateData);
        }

        return $model;
    }

    public function deleteForOrganization(int $organizationId, int $id): bool
    {
        $model = BrandAudienceModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        return (bool) $model->delete();
    }
}
