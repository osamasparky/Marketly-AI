<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandGoalModel extends Model
{
    use HasFactory;

    protected $table = 'brand_goals';

    protected $fillable = [
        'organization_id',
        'brand_profile_id',
        'goal_type',
        'priority',
        'description',
        'target_metrics',
        'status',
    ];

    protected $casts = [
        'target_metrics' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfileModel::class, 'brand_profile_id');
    }
}
