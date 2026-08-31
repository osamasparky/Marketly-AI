<?php

namespace App\Domains\Publishing\Domain\Services;

use App\Domains\Publishing\Domain\Adapters\FacebookPublisherAdapter;
use App\Domains\Publishing\Domain\Adapters\InstagramPublisherAdapter;
use App\Domains\Publishing\Domain\Adapters\LinkedInPublisherAdapter;
use App\Domains\Publishing\Domain\Adapters\TikTokPublisherAdapter;
use App\Domains\Publishing\Domain\Adapters\XPublisherAdapter;
use App\Domains\Publishing\Domain\Contracts\SocialPublisherInterface;
use InvalidArgumentException;

class SocialPublisherFactory
{
    /**
     * Resolve publisher adapter for given platform.
     */
    public function make(string $platform): SocialPublisherInterface
    {
        return match (strtolower($platform)) {
            'linkedin' => new LinkedInPublisherAdapter(),
            'x', 'twitter' => new XPublisherAdapter(),
            'instagram' => new InstagramPublisherAdapter(),
            'tiktok' => new TikTokPublisherAdapter(),
            'facebook' => new FacebookPublisherAdapter(),
            default => throw new InvalidArgumentException("Unsupported social platform [{$platform}]."),
        };
    }
}
