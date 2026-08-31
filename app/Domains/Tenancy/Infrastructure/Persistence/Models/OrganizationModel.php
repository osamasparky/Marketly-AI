<?php

namespace App\Domains\Tenancy\Infrastructure\Persistence\Models;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationModel extends Model
{
    use HasFactory;

    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'status',
        'default_locale',
        'timezone',
        'ai_config_json',
        'website_url',
        'industry',
        'billing_email',
    ];

    protected function casts(): array
    {
        return [
            'ai_config_json' => 'encrypted:array',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembershipModel::class, 'organization_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(UserModel::class, 'organization_memberships', 'organization_id', 'user_id')
            ->withPivot('role_id', 'status', 'joined_at')
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitationModel::class, 'organization_id');
    }
}
