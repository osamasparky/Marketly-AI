<?php

namespace App\Domains\Strategy\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyPlatformModel extends Model
{
    use HasFactory;

    protected $table = 'strategy_platforms';

    protected $fillable = [
        'organization_id',
        'strategy_id',
        'platform',
        'primary_objective',
        'posting_frequency',
        'recommended_formats',
    ];

    protected $casts = [
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
