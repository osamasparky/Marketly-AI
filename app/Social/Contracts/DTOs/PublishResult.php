<?php

namespace App\Social\Contracts\DTOs;

class PublishResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalPostId = null,
        public readonly ?string $externalPostUrl = null,
        public readonly array $rawResponse = [],
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'external_post_id' => $this->externalPostId,
            'external_post_url' => $this->externalPostUrl,
            'raw_response' => $this->rawResponse,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
        ];
    }
}
