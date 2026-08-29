<?php

namespace App\Domains\Billing\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanEntitlementModel extends Model
{
    protected $table = 'plan_entitlements';

    protected $fillable = [
        'plan_id',
        'feature_key',
        'is_enabled',
        'limit_count',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'limit_count' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanModel::class, 'plan_id');
    }
}
