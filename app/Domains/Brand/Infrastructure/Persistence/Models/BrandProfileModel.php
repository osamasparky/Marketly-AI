<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BrandProfileModel extends Model
{
    use HasFactory;

    protected $table = 'brand_profiles';

    protected $fillable = [
        'organization_id',
        'business_name',
        'legal_name',
        'industry',
        'business_type',
        'description',
        'website',
        'phone',
        'email',
        'country',
        'region',
        'city',
        'timezone',
        'default_locale',
        'tagline',
        'mission',
        'vision',
        'values',
        'positioning',
        'unique_selling_points',
        'brand_promise',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'preferred_platforms',
        'content_pillars_input',
        'existing_social_handles',
        'approximate_monthly_budget',
        'budget_currency',
        'version',
        'status',
    ];

    protected $casts = [
        'values' => 'array',
        'unique_selling_points' => 'array',
        'preferred_platforms' => 'array',
        'content_pillars_input' => 'array',
        'existing_social_handles' => 'array',
        'approximate_monthly_budget' => 'decimal:2',
        'version' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function productsServices(): HasMany
    {
        return $this->hasMany(BrandProductServiceModel::class, 'brand_profile_id');
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(BrandAudienceModel::class, 'brand_profile_id');
    }

    public function voice(): HasOne
    {
        return $this->hasOne(BrandVoiceModel::class, 'brand_profile_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(BrandGoalModel::class, 'brand_profile_id');
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(BrandCompetitorModel::class, 'brand_profile_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(BrandLocationModel::class, 'brand_profile_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(BrandAssetModel::class, 'brand_profile_id');
    }
}
