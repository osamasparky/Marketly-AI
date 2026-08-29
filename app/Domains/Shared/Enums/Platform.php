<?php

namespace App\Domains\Shared\Enums;

enum Platform: string
{
    case FACEBOOK = 'facebook';
    case INSTAGRAM = 'instagram';
    case LINKEDIN = 'linkedin';
    case YOUTUBE = 'youtube';
    case TIKTOK = 'tiktok';
    case X = 'x';

    public function label(): string
    {
        return match ($this) {
            self::FACEBOOK => 'Facebook',
            self::INSTAGRAM => 'Instagram',
            self::LINKEDIN => 'LinkedIn',
            self::YOUTUBE => 'YouTube',
            self::TIKTOK => 'TikTok',
            self::X => 'X (formerly Twitter)',
        };
    }
}
