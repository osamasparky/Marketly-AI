<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Repositories;

use App\Domains\Brand\Application\DTOs\SaveBrandProfileData;
use App\Domains\Brand\Domain\Repositories\BrandProfileRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use Illuminate\Support\Facades\DB;

class EloquentBrandProfileRepository implements BrandProfileRepositoryInterface
{
    public function findByOrganizationId(int $organizationId): ?BrandProfileModel
    {
        return BrandProfileModel::where('organization_id', $organizationId)->first();
    }

    public function findWithRelationsByOrganizationId(int $organizationId): ?BrandProfileModel
    {
        return BrandProfileModel::with([
            'productsServices' => fn ($q) => $q->where('status', 'active'),
            'audiences' => fn ($q) => $q->where('status', 'active'),
            'voice',
            'goals' => fn ($q) => $q->where('status', 'active'),
            'competitors',
            'locations' => fn ($q) => $q->where('status', 'active'),
            'assets',
        ])->where('organization_id', $organizationId)->first();
    }

    public function saveForOrganization(int $organizationId, SaveBrandProfileData $data): BrandProfileModel
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $existing = BrandProfileModel::where('organization_id', $organizationId)->lockForUpdate()->first();
            $nextVersion = $existing ? ($existing->version + 1) : 1;

            return BrandProfileModel::updateOrCreate(
                ['organization_id' => $organizationId],
                [
                    'business_name' => $data->businessName,
                    'legal_name' => $data->legalName,
                    'industry' => $data->industry,
                    'business_type' => $data->businessType,
                    'description' => $data->description,
                    'website' => $data->website,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'country' => $data->country,
                    'region' => $data->region,
                    'city' => $data->city,
                    'timezone' => $data->timezone,
                    'default_locale' => $data->defaultLocale,
                    'tagline' => $data->tagline,
                    'mission' => $data->mission,
                    'vision' => $data->vision,
                    'values' => $data->values,
                    'positioning' => $data->positioning,
                    'unique_selling_points' => $data->uniqueSellingPoints,
                    'brand_promise' => $data->brandPromise,
                    'primary_color' => $data->primaryColor,
                    'secondary_color' => $data->secondaryColor,
                    'accent_color' => $data->accentColor,
                    'background_color' => $data->backgroundColor,
                    'version' => $nextVersion,
                ]
            );
        });
    }

    public function ensureExistsForOrganization(int $organizationId): BrandProfileModel
    {
        return BrandProfileModel::firstOrCreate(
            ['organization_id' => $organizationId],
            ['business_name' => 'My Business']
        );
    }
}
