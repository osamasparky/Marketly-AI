<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Repositories;

use App\Domains\Brand\Application\DTOs\SaveBrandVoiceData;
use App\Domains\Brand\Domain\Repositories\BrandVoiceRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;

class EloquentBrandVoiceRepository implements BrandVoiceRepositoryInterface
{
    public function findByOrganizationId(int $organizationId): ?BrandVoiceModel
    {
        return BrandVoiceModel::where('organization_id', $organizationId)->first();
    }

    public function saveForOrganization(int $organizationId, int $brandProfileId, SaveBrandVoiceData $data): BrandVoiceModel
    {
        return BrandVoiceModel::updateOrCreate(
            ['organization_id' => $organizationId],
            [
                'brand_profile_id' => $brandProfileId,
                'primary_tones' => $data->primaryTones,
                'formality_scale' => $data->formalityScale,
                'playfulness_scale' => $data->playfulnessScale,
                'boldness_scale' => $data->boldnessScale,
                'simplicity_scale' => $data->simplicityScale,
                'preferred_phrases' => $data->preferredPhrases,
                'forbidden_phrases' => $data->forbiddenPhrases,
                'words_to_avoid' => $data->wordsToAvoid,
                'words_to_emphasize' => $data->wordsToEmphasize,
                'cta_preferences' => $data->ctaPreferences,
                'emoji_style' => $data->emojiStyle,
                'hashtag_style' => $data->hashtagStyle,
                'dialect' => $data->dialect,
                'language_specific_notes' => $data->languageSpecificNotes,
            ]
        );
    }
}
