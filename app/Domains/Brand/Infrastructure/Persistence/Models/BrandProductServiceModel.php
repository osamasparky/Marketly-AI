<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Models;

use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandProductServiceModel extends Model
{
    use HasFactory;

    protected $table = 'brand_products_services';

    protected $fillable = [
        'organization_id',
        'brand_profile_id',
        'name',
        'type',
        'description',
        'category',
        'price',
        'currency',
        'url',
        'features',
        'target_audience_ids',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'target_audience_ids' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfileModel::class, 'brand_profile_id');
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BrandAssetModel::class, 'product_service_id')->where('type', 'product_image');
    }
}
