<?php

namespace App\Domains\Identity\Application\DTOs;

class AuthResultData
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $token,
        public readonly ?string $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->userId,
                'name' => $this->name,
                'email' => $this->email,
                'created_at' => $this->createdAt,
            ],
            'token' => $this->token,
            'token_type' => 'Bearer',
        ];
    }
}
