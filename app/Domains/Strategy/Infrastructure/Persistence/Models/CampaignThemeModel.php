<?php

namespace App\Domains\Strategy\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignThemeModel extends Model
{
    use HasFactory;

    protected $table = 'campaign_themes';

    protected $fillable = [
        'organization_id',
        'strategy_id',
        'name',
        'objective',
        'audience_persona',
        'core_message',
        'duration_weeks',
        'recommended_formats',
        'status',
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'recommended_formats' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(MarketingStrategyModel::class, 'strategy_id');
    }
}
