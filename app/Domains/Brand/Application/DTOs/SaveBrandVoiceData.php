<?php

namespace App\Domains\Brand\Application\DTOs;

readonly class SaveBrandVoiceData
{
    public function __construct(
        public array $primaryTones = ['professional'],
        public int $formalityScale = 3,
        public int $playfulnessScale = 2,
        public int $boldnessScale = 3,
        public int $simplicityScale = 4,
        public array $preferredPhrases = [],
        public array $forbiddenPhrases = [],
        public array $wordsToAvoid = [],
        public array $wordsToEmphasize = [],
        public array $ctaPreferences = [],
        public string $emojiStyle = 'moderate',
        public string $hashtagStyle = 'targeted',
        public string $dialect = 'saudi',
        public ?array $languageSpecificNotes = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            primaryTones: $data['primary_tones'] ?? ['professional'],
            formalityScale: (int) ($data['formality_scale'] ?? 3),
            playfulnessScale: (int) ($data['playfulness_scale'] ?? 2),
            boldnessScale: (int) ($data['boldness_scale'] ?? 3),
            simplicityScale: (int) ($data['simplicity_scale'] ?? 4),
            preferredPhrases: $data['preferred_phrases'] ?? [],
            forbiddenPhrases: $data['forbidden_phrases'] ?? [],
            wordsToAvoid: $data['words_to_avoid'] ?? [],
            wordsToEmphasize: $data['words_to_emphasize'] ?? [],
            ctaPreferences: $data['cta_preferences'] ?? [],
            emojiStyle: $data['emoji_style'] ?? 'moderate',
            hashtagStyle: $data['hashtag_style'] ?? 'targeted',
            dialect: $data['dialect'] ?? 'saudi',
            languageSpecificNotes: $data['language_specific_notes'] ?? null
        );
    }
}
