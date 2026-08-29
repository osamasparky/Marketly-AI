<?php

namespace App\Domains\Brand\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandVoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'primary_tones' => $this->primary_tones ?? [],
            'formality_scale' => $this->formality_scale,
            'playfulness_scale' => $this->playfulness_scale,
            'boldness_scale' => $this->boldness_scale,
            'simplicity_scale' => $this->simplicity_scale,
            'preferred_phrases' => $this->preferred_phrases ?? [],
            'forbidden_phrases' => $this->forbidden_phrases ?? [],
            'words_to_avoid' => $this->words_to_avoid ?? [],
            'words_to_emphasize' => $this->words_to_emphasize ?? [],
            'cta_preferences' => $this->cta_preferences ?? [],
            'emoji_style' => $this->emoji_style,
            'hashtag_style' => $this->hashtag_style,
            'dialect' => $this->dialect,
        ];
    }
}
