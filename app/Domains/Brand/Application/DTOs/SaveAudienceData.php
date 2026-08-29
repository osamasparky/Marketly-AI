<?php

namespace App\Domains\Brand\Application\DTOs;

readonly class SaveAudienceData
{
    public function __construct(
        public string $name,
        public string $type = 'b2c',
        public ?string $description = null,
        public ?string $ageRange = null,
        public string $gender = 'all',
        public array $locations = [],
        public array $interests = [],
        public array $painPoints = [],
        public array $needs = [],
        public ?string $industry = null,
        public ?string $companySize = null,
        public array $jobTitles = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim($data['name'] ?? ''),
            type: $data['type'] ?? 'b2c',
            description: $data['description'] ?? null,
            ageRange: $data['age_range'] ?? null,
            gender: $data['gender'] ?? 'all',
            locations: $data['locations'] ?? [],
            interests: $data['interests'] ?? [],
            painPoints: $data['pain_points'] ?? [],
            needs: $data['needs'] ?? [],
            industry: $data['industry'] ?? null,
            companySize: $data['company_size'] ?? null,
            jobTitles: $data['job_titles'] ?? []
        );
    }
}
