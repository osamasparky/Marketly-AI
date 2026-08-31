<?php

namespace App\Domains\Brand\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array with safe attribute exposure.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'legal_name' => $this->legal_name,
            'industry' => $this->industry,
            'business_type' => $this->business_type,
            'description' => $this->description,
            'website' => $this->website,
            'phone' => $this->phone,
            'email' => $this->email,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'timezone' => $this->timezone,
            'default_locale' => $this->default_locale,
            'tagline' => $this->tagline,
            'mission' => $this->mission,
            'vision' => $this->vision,
            'values' => $this->values ?? [],
            'positioning' => $this->positioning,
            'unique_selling_points' => $this->unique_selling_points ?? [],
            'brand_promise' => $this->brand_promise,
            'primary_color' => $this->primary_color ?? '#10B981',
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,
            'background_color' => $this->background_color,
            'preferred_platforms' => $this->preferred_platforms ?? [],
            'content_pillars' => $this->content_pillars_input ?? [],
            'existing_social_handles' => $this->existing_social_handles ?? [],
            'approximate_monthly_budget' => $this->approximate_monthly_budget ? (float) $this->approximate_monthly_budget : null,
            'budget_currency' => $this->budget_currency ?? 'SAR',
            'version' => $this->version,
            'status' => $this->status,
            'assets' => $this->whenLoaded('assets', function () {
                return $this->assets->map(function ($asset) {
                    return [
                        'id' => $asset->id,
                        'name' => $asset->name,
                        'type' => $asset->type,
                        'file_path' => $asset->file_path,
                        'public_url' => $asset->file_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($asset->file_path) : null,
                        'mime_type' => $asset->mime_type,
                        'file_size' => $asset->file_size,
                    ];
                });
            }),
            'products_services' => ProductServiceResource::collection($this->whenLoaded('productsServices')),
            'audiences' => AudienceResource::collection($this->whenLoaded('audiences')),
            'voice' => new BrandVoiceResource($this->whenLoaded('voice')),
            'goals' => GoalResource::collection($this->whenLoaded('goals')),
            'competitors' => CompetitorResource::collection($this->whenLoaded('competitors')),
        ];
    }
}
