<?php

namespace App\Domains\Analytics\Infrastructure\Persistence\Models;

use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentPillarModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendationModel extends Model
{
    protected $table = 'ai_recommendations';

    protected $fillable = [
        'organization_id',
        'strategy_id',
        'pillar_id',
        'type',
        'title',
        'explanation',
        'evidence_json',
        'action_json',
        'confidence_score',
        'status',
        'applied_at',
    ];

    protected $casts = [
        'evidence_json' => 'array',
        'action_json' => 'array',
        'confidence_score' => 'float',
        'applied_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(MarketingStrategyModel::class, 'strategy_id');
    }

    public function pillar(): BelongsTo
    {
        return $this->belongsTo(ContentPillarModel::class, 'pillar_id');
    }
}
