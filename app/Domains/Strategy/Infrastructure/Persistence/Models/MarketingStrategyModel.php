<?php

namespace App\Domains\Strategy\Infrastructure\Persistence\Models;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingStrategyModel extends Model
{
    use HasFactory;

    protected $table = 'marketing_strategies';

    protected $fillable = [
        'organization_id',
        'brand_profile_id',
        'name',
        'description',
        'primary_objective',
        'secondary_objectives',
        'status',
        'version',
        'start_date',
        'end_date',
        'rationale',
        'created_by',
    ];

    protected $casts = [
        'secondary_objectives' => 'array',
        'version' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfileModel::class, 'brand_profile_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }

    public function pillars(): HasMany
    {
        return $this->hasMany(ContentPillarModel::class, 'strategy_id');
    }

    public function campaignThemes(): HasMany
    {
        return $this->hasMany(CampaignThemeModel::class, 'strategy_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(ContentOpportunityModel::class, 'strategy_id');
    }

    public function platforms(): HasMany
    {
        return $this->hasMany(StrategyPlatformModel::class, 'strategy_id');
    }
}
