<?php

namespace App\Domains\Strategy\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPillarModel extends Model
{
    use HasFactory;

    protected $table = 'content_pillars';

    protected $fillable = [
        'organization_id',
        'strategy_id',
        'name',
        'description',
        'objective',
        'priority',
        'recommended_percentage',
        'status',
    ];

    protected $casts = [
        'recommended_percentage' => 'integer',
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
