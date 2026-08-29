<?php

namespace App\Domains\Tenancy\Infrastructure\Persistence\Models;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationInvitationModel extends Model
{
    use HasFactory;

    protected $table = 'organization_invitations';

    protected $fillable = [
        'organization_id',
        'email',
        'role_id',
        'token_hash',
        'status',
        'invited_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(RoleModel::class, 'role_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
