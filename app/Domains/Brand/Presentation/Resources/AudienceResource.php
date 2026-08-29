<?php

namespace App\Domains\Brand\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudienceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'age_range' => $this->age_range,
            'gender' => $this->gender,
            'locations' => $this->locations ?? [],
            'interests' => $this->interests ?? [],
            'pain_points' => $this->pain_points ?? [],
            'needs' => $this->needs ?? [],
            'industry' => $this->industry,
            'company_size' => $this->company_size,
            'job_titles' => $this->job_titles ?? [],
            'status' => $this->status,
        ];
    }
}
