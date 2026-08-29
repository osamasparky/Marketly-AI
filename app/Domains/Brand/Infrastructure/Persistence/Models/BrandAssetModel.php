<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandAssetModel extends Model
{
    use HasFactory;

    protected $table = 'brand_assets';

    protected $fillable = [
        'organization_id',
        'brand_profile_id',
        'name',
        'type',
        'file_path',
        'mime_type',
        'file_size',
        'is_public',
        'metadata',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'file_size' => 'integer',
        'metadata' => 'array',
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
