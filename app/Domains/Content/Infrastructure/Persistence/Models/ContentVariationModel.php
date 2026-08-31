<?php

namespace App\Domains\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentVariationModel extends Model
{
    use HasFactory;

    protected $table = 'content_variations';

    protected $fillable = [
        'organization_id',
        'content_post_id',
        'platform',
        'format',
        'hook',
        'body',
        'cta',
        'hashtags',
        'visual_brief',
        'thread_slides',
        'character_count',
        'status',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'visual_brief' => 'array',
        'thread_slides' => 'array',
        'character_count' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(ContentPostModel::class, 'content_post_id');
    }
}
