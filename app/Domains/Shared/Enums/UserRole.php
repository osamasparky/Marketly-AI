<?php

namespace App\Domains\Shared\Enums;

/**
 * Type-safe System Role Identifiers.
 * Note: Granular permissions are strictly database-backed and resolved via AuthorizationService.
 */
enum UserRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Administrator',
            self::MANAGER => 'Marketing Manager',
            self::EDITOR => 'Content Editor',
            self::VIEWER => 'Viewer',
        };
    }
}
