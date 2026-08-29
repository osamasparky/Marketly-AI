<?php

namespace App\Domains\Shared\Enums;

enum PublicationStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case SCHEDULED = 'scheduled';
    case PROCESSING = 'processing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Checks if a transition from this state to next state is strictly permitted.
     */
    public function canTransitionTo(PublicationStatus $next): bool
    {
        return match ($this) {
            self::DRAFT => in_array($next, [self::APPROVED, self::CANCELLED], true),
            self::APPROVED => in_array($next, [self::SCHEDULED, self::DRAFT, self::CANCELLED], true),
            self::SCHEDULED => in_array($next, [self::PROCESSING, self::APPROVED, self::CANCELLED], true),
            self::PROCESSING => in_array($next, [self::PUBLISHED, self::FAILED], true),
            self::PUBLISHED => false, // Terminal success
            self::FAILED => in_array($next, [self::SCHEDULED, self::DRAFT], true), // Can retry
            self::CANCELLED => in_array($next, [self::DRAFT], true),
        };
    }
}
