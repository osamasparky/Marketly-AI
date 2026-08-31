<?php

namespace App\Domains\Brand\Domain\Services;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;

class BrandCompletenessService
{
    /**
     * Calculate server-side completeness score (0-100%) and breakdown across the 6 pillars.
     *
     * @param BrandProfileModel|null $profile
     * @return array{
     *   total_score: int,
     *   pillars: array<string, array{name: string, score: int, max: int, is_complete: bool, missing: array<string>}>,
     *   status: string
     * }
     */
    public function calculate(?BrandProfileModel $profile): array
    {
        if (!$profile) {
            return [
                'total_score' => 0,
                'status' => 'empty',
                'pillars' => $this->emptyPillars(),
            ];
        }

        // 1. Business Profile (Max 20%)
        $businessMissing = [];
        $businessScore = 0;
        if (!empty($profile->business_name)) $businessScore += 6; else $businessMissing[] = 'business_name';
        if (!empty($profile->industry)) $businessScore += 4; else $businessMissing[] = 'industry';
        if (!empty($profile->business_type)) $businessScore += 4; else $businessMissing[] = 'business_type';
        if (!empty($profile->description)) $businessScore += 6; else $businessMissing[] = 'description';

        // 2. Brand Identity & Visual Assets (Max 20%)
        $identityMissing = [];
        $identityScore = 0;
        if (!empty($profile->tagline)) $identityScore += 3; else $identityMissing[] = 'tagline';
        if (!empty($profile->mission)) $identityScore += 3; else $identityMissing[] = 'mission';
        if (!empty($profile->vision)) $identityScore += 3; else $identityMissing[] = 'vision';
        if (!empty($profile->positioning)) $identityScore += 3; else $identityMissing[] = 'positioning';
        if (!empty($profile->values) && count($profile->values) > 0) $identityScore += 3; else $identityMissing[] = 'values';
        if (!empty($profile->primary_color)) $identityScore += 2; else $identityMissing[] = 'brand_colors';
        
        $hasLogo = $profile->assets()->where('type', 'logo')->exists();
        if ($hasLogo) $identityScore += 3; else $identityMissing[] = 'brand_logo';

        // 3. Target Audience (Max 20%)
        $audienceMissing = [];
        $audienceCount = $profile->audiences()->count();
        $audienceScore = 0;
        if ($audienceCount > 0) {
            $audienceScore = 20;
        } else {
            $audienceMissing[] = 'target_audiences';
        }

        // 4. Products & Services (Max 15%)
        $productMissing = [];
        $productCount = $profile->productsServices()->count();
        $productScore = 0;
        if ($productCount > 0) {
            $productScore = 15;
        } else {
            $productMissing[] = 'products_services';
        }

        // 5. Brand Voice (Max 15%)
        $voiceMissing = [];
        $voiceScore = 0;
        $voice = $profile->voice;
        if ($voice) {
            if (!empty($voice->primary_tones) && count($voice->primary_tones) > 0) $voiceScore += 8; else $voiceMissing[] = 'primary_tones';
            if (!empty($voice->dialect)) $voiceScore += 4; else $voiceMissing[] = 'dialect';
            if ($voice->formality_scale > 0) $voiceScore += 3;
        } else {
            $voiceMissing[] = 'brand_voice_profile';
        }

        // 6. Goals (Max 10%)
        $goalsMissing = [];
        $goalsCount = $profile->goals()->count();
        $goalsScore = 0;
        if ($goalsCount > 0) {
            $goalsScore = 10;
        } else {
            $goalsMissing[] = 'business_goals';
        }

        $totalScore = $businessScore + $identityScore + $audienceScore + $productScore + $voiceScore + $goalsScore;

        $pillars = [
            'business' => [
                'name' => 'Business Profile',
                'score' => $businessScore,
                'max' => 20,
                'is_complete' => $businessScore === 20,
                'missing' => $businessMissing,
            ],
            'identity' => [
                'name' => 'Brand Identity',
                'score' => $identityScore,
                'max' => 20,
                'is_complete' => $identityScore === 20,
                'missing' => $identityMissing,
            ],
            'audience' => [
                'name' => 'Target Audience',
                'score' => $audienceScore,
                'max' => 20,
                'is_complete' => $audienceScore === 20,
                'missing' => $audienceMissing,
            ],
            'products' => [
                'name' => 'Products & Services',
                'score' => $productScore,
                'max' => 15,
                'is_complete' => $productScore === 15,
                'missing' => $productMissing,
            ],
            'voice' => [
                'name' => 'Brand Voice & Tone',
                'score' => $voiceScore,
                'max' => 15,
                'is_complete' => $voiceScore === 15,
                'missing' => $voiceMissing,
            ],
            'goals' => [
                'name' => 'Marketing Goals',
                'score' => $goalsScore,
                'max' => 10,
                'is_complete' => $goalsScore === 10,
                'missing' => $goalsMissing,
            ],
        ];

        return [
            'total_score' => $totalScore,
            'status' => $totalScore >= 80 ? 'optimal' : ($totalScore >= 50 ? 'moderate' : 'incomplete'),
            'pillars' => $pillars,
        ];
    }

    private function emptyPillars(): array
    {
        return [
            'business' => ['name' => 'Business Profile', 'score' => 0, 'max' => 20, 'is_complete' => false, 'missing' => ['business_name', 'industry', 'description']],
            'identity' => ['name' => 'Brand Identity', 'score' => 0, 'max' => 20, 'is_complete' => false, 'missing' => ['tagline', 'mission', 'positioning']],
            'audience' => ['name' => 'Target Audience', 'score' => 0, 'max' => 20, 'is_complete' => false, 'missing' => ['target_audiences']],
            'products' => ['name' => 'Products & Services', 'score' => 0, 'max' => 15, 'is_complete' => false, 'missing' => ['products_services']],
            'voice' => ['name' => 'Brand Voice & Tone', 'score' => 0, 'max' => 15, 'is_complete' => false, 'missing' => ['brand_voice_profile']],
            'goals' => ['name' => 'Marketing Goals', 'score' => 0, 'max' => 10, 'is_complete' => false, 'missing' => ['business_goals']],
        ];
    }
}
