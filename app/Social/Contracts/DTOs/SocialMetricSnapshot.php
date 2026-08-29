<?php

namespace App\Social\Contracts\DTOs;

class SocialMetricSnapshot
{
    public function __construct(
        public readonly int $views = 0,
        public readonly int $reach = 0,
        public readonly int $likes = 0,
        public readonly int $comments = 0,
        public readonly int $shares = 0,
        public readonly int $saves = 0,
        public readonly int $clicks = 0,
        public readonly int $followersDelta = 0,
        public readonly array $rawMetrics = []
    ) {}

    public function toArray(): array
    {
        return [
            'views' => $this->views,
            'reach' => $this->reach,
            'likes' => $this->likes,
            'comments' => $this->comments,
            'shares' => $this->shares,
            'saves' => $this->saves,
            'clicks' => $this->clicks,
            'followers_delta' => $this->followersDelta,
            'raw_metrics' => $this->rawMetrics,
        ];
    }
}
