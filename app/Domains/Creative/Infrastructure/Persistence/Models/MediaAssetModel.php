<?php

namespace App\Domains\Creative\Infrastructure\Persistence\Models;

use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAssetModel extends Model
{
    use HasFactory;

    protected $table = 'media_assets';

    protected $fillable = [
        'organization_id',
        'brand_profile_id',
        'content_post_id',
        'created_by',
        'title',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size_bytes',
        'width',
        'height',
        'aspect_ratio',
        'prompt_used',
        'visual_style',
        'text_overlay',
        'color_palette',
        'metadata',
        'status',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'color_palette' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'public_url',
    ];

    public function getPublicUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->file_path);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ContentPostModel::class, 'content_post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
