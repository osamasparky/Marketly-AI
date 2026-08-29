<?php

namespace App\Domains\Strategy\Application\Services;

use App\Domains\Brand\Domain\Services\BrandContextBuilder;
use InvalidArgumentException;

class StrategyContextBuilder
{
    public function __construct(
        private readonly BrandContextBuilder $brandContextBuilder
    ) {}

    /**
     * Assemble sanitized, minimized context for strategy generation.
     */
    public function build(
        int $organizationId,
        string $primaryObjective,
        array $targetPlatforms,
        int $timeHorizonMonths = 3,
        ?string $seasonalFocus = null,
        ?int $targetAudienceId = null
    ): array {
        if ($organizationId <= 0) {
            throw new InvalidArgumentException('Valid organization ID is required.');
        }

        $brandContext = $this->brandContextBuilder->build($organizationId);
        $minimizedBrand = $brandContext->forContentGeneration($targetAudienceId);

        return [
            'strategic_parameters' => [
                'primary_objective' => $primaryObjective,
                'time_horizon_months' => $timeHorizonMonths,
                'target_platforms' => $targetPlatforms,
                'seasonal_focus' => $seasonalFocus,
            ],
            'brand_intelligence' => [
                'business_name' => $minimizedBrand['business']['name'],
                'industry' => $minimizedBrand['business']['industry'],
                'business_type' => $minimizedBrand['business']['business_type'],
                'description' => $minimizedBrand['business']['description'],
                'country' => $minimizedBrand['business']['country'],
                'locale' => $minimizedBrand['business']['locale'],
                'positioning' => $minimizedBrand['brand_identity']['positioning'] ?? null,
                'voice_dialect' => $minimizedBrand['voice_and_tone']['dialect'] ?? 'saudi',
                'formality_scale' => $minimizedBrand['voice_and_tone']['formality_scale'] ?? 3,
                'target_audience' => $minimizedBrand['target_audience'],
                'featured_product' => $minimizedBrand['featured_product'],
            ],
        ];
    }
}
