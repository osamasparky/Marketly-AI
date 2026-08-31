<?php

namespace App\Domains\Content\Domain\Services;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandAudienceModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandGoalModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProductServiceModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\CampaignThemeModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentPillarModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;

class ContentContextBuilder
{
    /**
     * Build bounded, sanitized context for content generation scoped strictly per brand.
     */
    public function build(
        int $organizationId,
        ?int $pillarId = null,
        ?int $campaignThemeId = null,
        ?int $productId = null,
        ?int $audienceId = null,
        ?string $platform = 'linkedin',
        ?string $tone = null,
        ?string $dialect = null,
        ?string $language = 'ar',
        ?int $brandId = null
    ): array {
        $profile = $brandId
            ? BrandProfileModel::where('id', $brandId)->where('organization_id', $organizationId)->first()
            : BrandProfileModel::where('organization_id', $organizationId)->first();

        $voice = $brandId
            ? BrandVoiceModel::where('brand_profile_id', $brandId)->first()
            : BrandVoiceModel::where('organization_id', $organizationId)->first();

        // Selected or default product
        $product = null;
        if ($productId) {
            $product = BrandProductServiceModel::where('organization_id', $organizationId)->where('id', $productId)->first();
        }
        if (!$product && $brandId) {
            $product = BrandProductServiceModel::where('organization_id', $organizationId)->where('brand_profile_id', $brandId)->where('status', 'active')->first();
        }
        if (!$product) {
            $product = BrandProductServiceModel::where('organization_id', $organizationId)->where('status', 'active')->first();
        }

        // Selected or default audience
        $audience = null;
        if ($audienceId) {
            $audience = BrandAudienceModel::where('organization_id', $organizationId)->where('id', $audienceId)->first();
        }
        if (!$audience && $brandId) {
            $audience = BrandAudienceModel::where('organization_id', $organizationId)->where('brand_profile_id', $brandId)->where('status', 'active')->first();
        }
        if (!$audience) {
            $audience = BrandAudienceModel::where('organization_id', $organizationId)->where('status', 'active')->first();
        }

        // Selected or active strategy & pillar
        $pillar = null;
        $strategy = null;
        if ($pillarId) {
            $pillar = ContentPillarModel::where('organization_id', $organizationId)->where('id', $pillarId)->first();
            $strategy = $pillar ? MarketingStrategyModel::find($pillar->strategy_id) : null;
        }

        if (!$pillar) {
            $strategyQuery = MarketingStrategyModel::where('organization_id', $organizationId)
                ->where('status', 'active');

            if ($brandId) {
                $strategyQuery->where('brand_profile_id', $brandId);
            }

            $strategy = $strategyQuery->latest()->first();

            $pillar = $strategy?->pillars()->where('status', 'active')->first();
        }

        // Selected or active campaign theme
        $theme = null;
        if ($campaignThemeId) {
            $theme = CampaignThemeModel::where('organization_id', $organizationId)->where('id', $campaignThemeId)->first();
        } elseif ($strategy) {
            $theme = $strategy->campaignThemes()->first();
        }

        // Voice and language normalization
        $selectedTone = $tone ?: ($voice?->primary_tones[0] ?? 'professional');
        $selectedDialect = $dialect ?: ($voice?->dialect ?? 'saudi');
        $selectedLanguage = $language ?: 'ar';

        return [
            'brand' => [
                'id' => $profile?->id,
                'business_name' => $profile?->business_name ?? 'Our Brand',
                'industry' => $profile?->industry ?? 'Business & Technology',
                'description' => $profile?->description ?? '',
                'tagline' => $profile?->tagline ?? '',
                'positioning' => $profile?->positioning ?? '',
                'tone' => $selectedTone,
                'formality' => $voice?->formality_scale ?? 'balanced',
                'dialect' => $selectedDialect,
                'emoji_style' => $voice?->emoji_style ?? 'moderate',
                'restrictions' => $voice?->forbidden_phrases ?? [],
                'vocabulary_blacklist' => $voice?->words_to_avoid ?? [],
            ],
            'product' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'description' => $product->description,
                'price' => $product->price ? "{$product->price} {$product->currency}" : null,
                'features' => $product->features ?? [],
            ] : null,
            'audience' => $audience ? [
                'id' => $audience->id,
                'name' => $audience->name,
                'persona_summary' => $audience->description,
                'pain_points' => $audience->pain_points ?? [],
                'goals' => $audience->goals ?? [],
            ] : null,
            'strategic_anchor' => [
                'strategy_id' => $strategy?->id,
                'strategy_name' => $strategy?->name,
                'pillar_id' => $pillar?->id,
                'pillar_name' => $pillar?->name,
                'pillar_description' => $pillar?->description,
                'pillar_objective' => $pillar?->objective,
                'theme_id' => $theme?->id,
                'theme_name' => $theme?->name,
                'core_message' => $theme?->core_message,
            ],
            'generation_parameters' => [
                'platform' => $platform,
                'tone' => $selectedTone,
                'dialect' => $selectedDialect,
                'language' => $selectedLanguage,
            ],
        ];
    }
}
