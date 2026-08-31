<?php

namespace App\Domains\Brand\Application\DTOs;

readonly class SaveBrandProfileData
{
    public function __construct(
        public string $businessName,
        public ?string $legalName = null,
        public string $industry = 'Technology',
        public string $businessType = 'B2B',
        public ?string $description = null,
        public ?string $website = null,
        public ?string $phone = null,
        public ?string $email = null,
        public string $country = 'SA',
        public ?string $region = null,
        public ?string $city = null,
        public string $timezone = 'Asia/Riyadh',
        public string $defaultLocale = 'ar',
        public ?string $tagline = null,
        public ?string $mission = null,
        public ?string $vision = null,
        public array $values = [],
        public ?string $positioning = null,
        public array $uniqueSellingPoints = [],
        public ?string $brandPromise = null,
        public ?string $primaryColor = '#10B981',
        public ?string $secondaryColor = null,
        public ?string $accentColor = null,
        public ?string $backgroundColor = null,
        public ?int $id = null,
        public array $preferredPlatforms = [],
        public array $contentPillarsInput = [],
        public array $existingSocialHandles = [],
        public ?float $approximateMonthlyBudget = null,
        public string $budgetCurrency = 'SAR'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            businessName: trim($data['business_name'] ?? 'My Business'),
            legalName: $data['legal_name'] ?? null,
            industry: $data['industry'] ?? 'Technology',
            businessType: $data['business_type'] ?? 'B2B',
            description: $data['description'] ?? null,
            website: $data['website'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            country: $data['country'] ?? 'SA',
            region: $data['region'] ?? null,
            city: $data['city'] ?? null,
            timezone: $data['timezone'] ?? 'Asia/Riyadh',
            defaultLocale: $data['default_locale'] ?? 'ar',
            tagline: $data['tagline'] ?? null,
            mission: $data['mission'] ?? null,
            vision: $data['vision'] ?? null,
            values: $data['values'] ?? [],
            positioning: $data['positioning'] ?? null,
            uniqueSellingPoints: $data['unique_selling_points'] ?? [],
            brandPromise: $data['brand_promise'] ?? null,
            primaryColor: $data['primary_color'] ?? '#10B981',
            secondaryColor: $data['secondary_color'] ?? null,
            accentColor: $data['accent_color'] ?? null,
            backgroundColor: $data['background_color'] ?? null,
            id: isset($data['id']) ? (int) $data['id'] : null,
            preferredPlatforms: $data['preferred_platforms'] ?? [],
            contentPillarsInput: $data['content_pillars'] ?? $data['content_pillars_input'] ?? [],
            existingSocialHandles: $data['existing_social_handles'] ?? [],
            approximateMonthlyBudget: isset($data['approximate_monthly_budget']) ? (float) $data['approximate_monthly_budget'] : null,
            budgetCurrency: $data['budget_currency'] ?? 'SAR'
        );
    }
}
