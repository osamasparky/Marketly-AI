<?php

namespace App\Domains\Brand\Application\DTOs;

readonly class SaveCompetitorData
{
    public function __construct(
        public string $name,
        public ?string $website = null,
        public ?string $description = null,
        public ?string $positioning = null,
        public array $strengths = [],
        public array $weaknesses = [],
        public ?string $notes = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim($data['name'] ?? ''),
            website: $data['website'] ?? null,
            description: $data['description'] ?? null,
            positioning: $data['positioning'] ?? null,
            strengths: $data['strengths'] ?? [],
            weaknesses: $data['weaknesses'] ?? [],
            notes: $data['notes'] ?? null
        );
    }
}
