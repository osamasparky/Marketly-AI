<?php

namespace App\Domains\Brand\Domain\Services;

use App\Domains\Brand\Domain\DTOs\BrandContext;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use InvalidArgumentException;

class BrandContextBuilder
{
    /**
     * Assemble full, validated, tenant-isolated BrandContext DTO from database models.
     */
    public function build(int $organizationId): BrandContext
    {
        if ($organizationId <= 0) {
            throw new InvalidArgumentException('Valid organization ID is required to build BrandContext.');
        }

        $profile = BrandProfileModel::with([
            'productsServices' => fn ($q) => $q->where('status', 'active'),
            'audiences' => fn ($q) => $q->where('status', 'active'),
            'voice',
            'goals' => fn ($q) => $q->where('status', 'active'),
            'competitors',
            'locations' => fn ($q) => $q->where('status', 'active'),
        ])->where('organization_id', $organizationId)->first();

        if (!$profile) {
            // Return minimal fallback context
            return new BrandContext(
                organizationId: $organizationId,
                businessName: "Organization #{$organizationId}",
                industry: 'General',
                businessType: 'B2B',
                description: 'AI-assisted business workspace',
                defaultLocale: 'en',
                country: 'SA',
                region: null,
                city: null,
                brandIdentity: [],
                voiceAndTone: [
                    'primary_tones' => ['professional'],
                    'formality_scale' => 3,
                    'dialect' => 'saudi',
                ],
                audiences: [],
                productsAndServices: [],
                goals: [],
                competitors: [],
                locations: []
            );
        }

        $brandIdentity = [
            'tagline' => $profile->tagline,
            'mission' => $profile->mission,
            'vision' => $profile->vision,
            'values' => $profile->values ?? [],
            'positioning' => $profile->positioning,
            'unique_selling_points' => $profile->unique_selling_points ?? [],
            'brand_promise' => $profile->brand_promise,
        ];

        $voice = $profile->voice;
        $voiceAndTone = $voice ? [
            'primary_tones' => $voice->primary_tones ?? ['professional'],
            'formality_scale' => $voice->formality_scale,
            'playfulness_scale' => $voice->playfulness_scale,
            'boldness_scale' => $voice->boldness_scale,
            'simplicity_scale' => $voice->simplicity_scale,
            'preferred_phrases' => $voice->preferred_phrases ?? [],
            'forbidden_phrases' => $voice->forbidden_phrases ?? [],
            'words_to_avoid' => $voice->words_to_avoid ?? [],
            'words_to_emphasize' => $voice->words_to_emphasize ?? [],
            'cta_preferences' => $voice->cta_preferences ?? [],
            'emoji_style' => $voice->emoji_style,
            'hashtag_style' => $voice->hashtag_style,
            'dialect' => $voice->dialect,
        ] : [
            'primary_tones' => ['professional'],
            'formality_scale' => 3,
            'dialect' => 'saudi',
        ];

        $audiences = $profile->audiences->map(function ($aud) {
            return [
                'id' => $aud->id,
                'name' => $aud->name,
                'type' => $aud->type,
                'description' => $aud->description,
                'age_range' => $aud->age_range,
                'gender' => $aud->gender,
                'interests' => $aud->interests ?? [],
                'pain_points' => $aud->pain_points ?? [],
                'needs' => $aud->needs ?? [],
                'industry' => $aud->industry,
                'company_size' => $aud->company_size,
                'job_titles' => $aud->job_titles ?? [],
            ];
        })->toArray();

        $products = $profile->productsServices->map(function ($prod) {
            return [
                'id' => $prod->id,
                'name' => $prod->name,
                'type' => $prod->type,
                'description' => $prod->description,
                'category' => $prod->category,
                'price' => $prod->price,
                'currency' => $prod->currency,
                'features' => $prod->features ?? [],
            ];
        })->toArray();

        $goals = $profile->goals->map(function ($goal) {
            return [
                'id' => $goal->id,
                'goal_type' => $goal->goal_type,
                'priority' => $goal->priority,
                'description' => $goal->description,
                'target_metrics' => $goal->target_metrics ?? [],
            ];
        })->toArray();

        $competitors = $profile->competitors->map(function ($comp) {
            return [
                'id' => $comp->id,
                'name' => $comp->name,
                'website' => $comp->website,
                'positioning' => $comp->positioning,
                'strengths' => $comp->strengths ?? [],
                'weaknesses' => $comp->weaknesses ?? [],
            ];
        })->toArray();

        $locations = $profile->locations->map(function ($loc) {
            return [
                'id' => $loc->id,
                'name' => $loc->name,
                'country' => $loc->country,
                'city' => $loc->city,
            ];
        })->toArray();

        return new BrandContext(
            organizationId: $organizationId,
            businessName: $profile->business_name,
            industry: $profile->industry,
            businessType: $profile->business_type,
            description: $profile->description ?? '',
            defaultLocale: $profile->default_locale,
            country: $profile->country,
            region: $profile->region,
            city: $profile->city,
            brandIdentity: $brandIdentity,
            voiceAndTone: $voiceAndTone,
            audiences: $audiences,
            productsAndServices: $products,
            goals: $goals,
            competitors: $competitors,
            locations: $locations
        );
    }
}
