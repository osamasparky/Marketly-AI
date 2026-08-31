<?php

namespace App\Domains\Publishing\Infrastructure\Persistence\Models;

use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentVariationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishingJobModel extends Model
{
    protected $table = 'publishing_jobs';

    protected $fillable = [
        'organization_id',
        'content_post_id',
        'content_variation_id',
        'social_account_id',
        'idempotency_key',
        'status',
        'scheduled_at',
        'published_at',
        'external_post_id',
        'external_post_url',
        'attempts',
        'max_attempts',
        'last_error',
        'payload_snapshot',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'payload_snapshot' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ContentPostModel::class, 'content_post_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ContentVariationModel::class, 'content_variation_id');
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccountModel::class, 'social_account_id');
    }
}
