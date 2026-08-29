<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandAudienceModel extends Model
{
    use HasFactory;

    protected $table = 'brand_audiences';

    protected $fillable = [
        'organization_id',
        'brand_profile_id',
        'name',
        'type',
        'description',
        'age_range',
        'gender',
        'locations',
        'interests',
        'pain_points',
        'needs',
        'buying_behavior',
        'income_level',
        'industry',
        'company_size',
        'job_titles',
        'decision_makers',
        'business_challenges',
        'status',
    ];

    protected $casts = [
        'locations' => 'array',
        'interests' => 'array',
        'pain_points' => 'array',
        'needs' => 'array',
        'job_titles' => 'array',
        'decision_makers' => 'array',
        'business_challenges' => 'array',
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
