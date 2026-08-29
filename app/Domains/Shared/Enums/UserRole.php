<?php

namespace App\Domains\Shared\Enums;

enum UserRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    public function canApproveContent(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN, self::MANAGER], true);
    }

    public function canManageSocialAccounts(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN], true);
    }

    public function canPublish(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN, self::MANAGER], true);
    }
}
