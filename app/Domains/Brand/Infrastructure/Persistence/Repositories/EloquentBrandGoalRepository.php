<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Repositories;

use App\Domains\Brand\Application\DTOs\SaveGoalData;
use App\Domains\Brand\Domain\Repositories\BrandGoalRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandGoalModel;
use Illuminate\Support\Collection;

class EloquentBrandGoalRepository implements BrandGoalRepositoryInterface
{
    public function listByOrganizationId(int $organizationId): Collection
    {
        return BrandGoalModel::where('organization_id', $organizationId)->get();
    }

    public function findByIdForOrganization(int $organizationId, int $id): ?BrandGoalModel
    {
        return BrandGoalModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->first();
    }

    public function createForOrganization(int $organizationId, int $brandProfileId, SaveGoalData $data): BrandGoalModel
    {
        return BrandGoalModel::create([
            'organization_id' => $organizationId,
            'brand_profile_id' => $brandProfileId,
            'goal_type' => $data->goalType,
            'priority' => $data->priority,
            'description' => $data->description,
            'target_metrics' => $data->targetMetrics,
            'status' => 'active',
        ]);
    }

    public function updateForOrganization(int $organizationId, int $id, SaveGoalData $data): BrandGoalModel
    {
        $model = BrandGoalModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        $updateData = [];
        if ($data->goalType !== '') {
            $updateData['goal_type'] = $data->goalType;
        }
        if ($data->priority !== '') {
            $updateData['priority'] = $data->priority;
        }
        if ($data->description !== null) {
            $updateData['description'] = $data->description;
        }
        if (!empty($data->targetMetrics)) {
            $updateData['target_metrics'] = $data->targetMetrics;
        }

        if (!empty($updateData)) {
            $model->update($updateData);
        }

        return $model;
    }

    public function deleteForOrganization(int $organizationId, int $id): bool
    {
        $model = BrandGoalModel::where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        return (bool) $model->delete();
    }
}
