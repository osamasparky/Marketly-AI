<?php

namespace App\Domains\Billing\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanModel extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_annual',
        'currency',
        'trial_days',
        'is_active',
    ];

    protected $casts = [
        'price_monthly' => 'float',
        'price_annual' => 'float',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function entitlements(): HasMany
    {
        return $this->hasMany(PlanEntitlementModel::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SubscriptionModel::class, 'plan_id');
    }
}
