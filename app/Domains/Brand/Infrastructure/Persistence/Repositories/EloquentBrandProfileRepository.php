<?php

namespace App\Domains\Brand\Infrastructure\Persistence\Repositories;

use App\Domains\Brand\Application\DTOs\SaveBrandProfileData;
use App\Domains\Brand\Domain\Repositories\BrandProfileRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use Illuminate\Support\Facades\DB;

class EloquentBrandProfileRepository implements BrandProfileRepositoryInterface
{
    public function findByOrganizationId(int $organizationId, ?int $brandProfileId = null): ?BrandProfileModel
    {
        $query = BrandProfileModel::where('organization_id', $organizationId);
        if ($brandProfileId) {
            $query->where('id', $brandProfileId);
        }
        return $query->first();
    }

    public function findWithRelationsByOrganizationId(int $organizationId, ?int $brandProfileId = null): ?BrandProfileModel
    {
        $query = BrandProfileModel::with([
            'productsServices' => fn ($q) => $q->where('status', 'active'),
            'audiences' => fn ($q) => $q->where('status', 'active'),
            'voice',
            'goals' => fn ($q) => $q->where('status', 'active'),
            'competitors',
            'locations' => fn ($q) => $q->where('status', 'active'),
            'assets',
        ])->where('organization_id', $organizationId);

        if ($brandProfileId) {
            $query->where('id', $brandProfileId);
        }

        return $query->first();
    }

    public function listByOrganizationId(int $organizationId): \Illuminate\Support\Collection
    {
        return BrandProfileModel::where('organization_id', $organizationId)
            ->with(['assets'])
            ->orderBy('id', 'asc')
            ->get();
    }

    public function saveForOrganization(int $organizationId, SaveBrandProfileData $data, ?int $brandProfileId = null): BrandProfileModel
    {
        return DB::transaction(function () use ($organizationId, $data, $brandProfileId) {
            $targetId = $brandProfileId ?: $data->id;

            $existing = null;
            if ($targetId) {
                $existing = BrandProfileModel::where('organization_id', $organizationId)->where('id', $targetId)->lockForUpdate()->first();
            } else {
                $existing = BrandProfileModel::where('organization_id', $organizationId)->where('business_name', $data->businessName)->lockForUpdate()->first();
            }

            $nextVersion = $existing ? ($existing->version + 1) : 1;

            $attributes = [
                'organization_id' => $organizationId,
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
                'preferred_platforms' => $data->preferredPlatforms,
                'content_pillars_input' => $data->contentPillarsInput,
                'existing_social_handles' => $data->existingSocialHandles,
                'approximate_monthly_budget' => $data->approximateMonthlyBudget,
                'budget_currency' => $data->budgetCurrency,
                'version' => $nextVersion,
            ];

            if ($existing) {
                $existing->update($attributes);
                return $existing->fresh();
            }

            return BrandProfileModel::create($attributes);
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
