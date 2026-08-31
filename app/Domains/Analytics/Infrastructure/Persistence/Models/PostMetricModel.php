<?php

namespace App\Domains\Analytics\Infrastructure\Persistence\Models;

use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMetricModel extends Model
{
    protected $table = 'post_metrics';

    protected $fillable = [
        'organization_id',
        'content_post_id',
        'social_account_id',
        'captured_at',
        'views',
        'reach',
        'likes',
        'comments',
        'shares',
        'saves',
        'clicks',
        'engagement_rate',
        'metrics_json',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'views' => 'integer',
        'reach' => 'integer',
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'saves' => 'integer',
        'clicks' => 'integer',
        'engagement_rate' => 'float',
        'metrics_json' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ContentPostModel::class, 'content_post_id');
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccountModel::class, 'social_account_id');
    }
}
