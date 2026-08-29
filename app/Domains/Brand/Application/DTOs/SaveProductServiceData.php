<?php

namespace App\Domains\Brand\Application\DTOs;

readonly class SaveProductServiceData
{
    public function __construct(
        public string $name,
        public string $type = 'product',
        public ?string $description = null,
        public ?string $category = null,
        public ?float $price = null,
        public string $currency = 'SAR',
        public ?string $url = null,
        public array $features = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim($data['name'] ?? ''),
            type: $data['type'] ?? 'product',
            description: $data['description'] ?? null,
            category: $data['category'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            currency: $data['currency'] ?? 'SAR',
            url: $data['url'] ?? null,
            features: $data['features'] ?? []
        );
    }
}
