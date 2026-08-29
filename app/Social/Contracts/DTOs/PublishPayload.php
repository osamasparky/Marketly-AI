<?php

namespace App\Social\Contracts\DTOs;

class PublishPayload
{
    public function __construct(
        public readonly string $contentPostId,
        public readonly string $text,
        public readonly array $mediaUrls = [],
        public readonly string $mediaType = 'text', // text, image, carousel, video, story, reel
        public readonly array $platformOptions = [],
        public readonly string $idempotencyKey = ''
    ) {}

    public function toArray(): array
    {
        return [
            'content_post_id' => $this->contentPostId,
            'text' => $this->text,
            'media_urls' => $this->mediaUrls,
            'media_type' => $this->mediaType,
            'platform_options' => $this->platformOptions,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}
