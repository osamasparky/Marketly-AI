<?php

namespace App\Domains\Content\Infrastructure\Persistence\Models;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\CampaignThemeModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentPillarModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContentPostModel extends Model
{
    use HasFactory;

    protected $table = 'content_posts';

    protected $fillable = [
        'organization_id',
        'brand_profile_id',
        'strategy_id',
        'pillar_id',
        'campaign_theme_id',
        'title',
        'hook',
        'caption',
        'cta',
        'hashtags',
        'primary_platform',
        'content_type',
        'language',
        'dialect',
        'tone',
        'objective',
        'visual_brief',
        'metadata',
        'status',
        'scheduled_at',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'visual_brief' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfileModel::class, 'brand_profile_id');
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(MarketingStrategyModel::class, 'strategy_id');
    }

    public function pillar(): BelongsTo
    {
        return $this->belongsTo(ContentPillarModel::class, 'pillar_id');
    }

    public function campaignTheme(): BelongsTo
    {
        return $this->belongsTo(CampaignThemeModel::class, 'campaign_theme_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ContentVariationModel::class, 'content_post_id');
    }

    public function qualityAudits(): HasMany
    {
        return $this->hasMany(ContentQualityAuditModel::class, 'content_post_id')->latest();
    }

    public function latestAudit(): HasOne
    {
        return $this->hasOne(ContentQualityAuditModel::class, 'content_post_id')->latestOfMany();
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(\App\Domains\Creative\Infrastructure\Persistence\Models\MediaAssetModel::class, 'content_post_id');
    }
}
