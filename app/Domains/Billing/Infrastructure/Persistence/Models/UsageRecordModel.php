<?php

namespace App\Domains\Billing\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecordModel extends Model
{
    protected $table = 'usage_records';

    protected $fillable = [
        'organization_id',
        'brand_profile_id',
        'feature_key',
        'period_start',
        'period_end',
        'used_count',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'used_count' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel::class, 'brand_profile_id');
    }
}
