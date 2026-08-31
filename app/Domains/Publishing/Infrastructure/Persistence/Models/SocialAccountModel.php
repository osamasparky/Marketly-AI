<?php

namespace App\Domains\Publishing\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccountModel extends Model
{
    protected $table = 'social_accounts';

    protected $fillable = [
        'organization_id',
        'user_id',
        'platform',
        'account_name',
        'account_id',
        'account_username',
        'account_avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'is_active',
        'health_status',
        'last_health_check_at',
        'metadata',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'last_health_check_at' => 'datetime',
        'scopes' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function publishingJobs(): HasMany
    {
        return $this->hasMany(PublishingJobModel::class, 'social_account_id');
    }
}
