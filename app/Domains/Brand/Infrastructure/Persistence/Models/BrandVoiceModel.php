<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandVoiceModel extends Model
{
    use HasFactory;

    protected $table = 'brand_voices';

    protected $fillable = [
        'organization_id',
        'brand_profile_id',
        'primary_tones',
        'formality_scale',
        'playfulness_scale',
        'boldness_scale',
        'simplicity_scale',
        'preferred_phrases',
        'forbidden_phrases',
        'words_to_avoid',
        'words_to_emphasize',
        'cta_preferences',
        'emoji_style',
        'hashtag_style',
        'dialect',
        'language_specific_notes',
    ];

    protected $casts = [
        'primary_tones' => 'array',
        'formality_scale' => 'integer',
        'playfulness_scale' => 'integer',
        'boldness_scale' => 'integer',
        'simplicity_scale' => 'integer',
        'preferred_phrases' => 'array',
        'forbidden_phrases' => 'array',
        'words_to_avoid' => 'array',
        'words_to_emphasize' => 'array',
        'cta_preferences' => 'array',
        'language_specific_notes' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfileModel::class, 'brand_profile_id');
    }
}
