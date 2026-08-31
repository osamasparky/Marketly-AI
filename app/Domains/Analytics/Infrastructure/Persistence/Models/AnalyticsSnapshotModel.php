<?php

namespace App\Domains\Analytics\Infrastructure\Persistence\Models;

use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsSnapshotModel extends Model
{
    protected $table = 'analytics_snapshots';

    protected $fillable = [
        'organization_id',
        'social_account_id',
        'platform',
        'captured_at',
        'followers_count',
        'followers_delta',
        'impressions_count',
        'engagements_count',
        'metrics_json',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'followers_count' => 'integer',
        'followers_delta' => 'integer',
        'impressions_count' => 'integer',
        'engagements_count' => 'integer',
        'metrics_json' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccountModel::class, 'social_account_id');
    }
}
