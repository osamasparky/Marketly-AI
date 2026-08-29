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
            'version' => $this->version,
            'status' => $this->status,
            'products_services' => ProductServiceResource::collection($this->whenLoaded('productsServices')),
            'audiences' => AudienceResource::collection($this->whenLoaded('audiences')),
            'voice' => new BrandVoiceResource($this->whenLoaded('voice')),
            'goals' => GoalResource::collection($this->whenLoaded('goals')),
            'competitors' => CompetitorResource::collection($this->whenLoaded('competitors')),
        ];
    }
}
