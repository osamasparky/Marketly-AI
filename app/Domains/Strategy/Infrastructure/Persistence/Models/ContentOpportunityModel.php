<?php

namespace App\Domains\Strategy\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentOpportunityModel extends Model
{
    use HasFactory;

    protected $table = 'content_opportunities';

    protected $fillable = [
        'organization_id',
        'strategy_id',
        'title',
        'description',
        'objective',
        'priority',
        'source',
        'recommended_timing',
        'status',
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
