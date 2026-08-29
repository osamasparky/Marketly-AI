<?php

namespace App\Domains\Brand\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'website' => $this->website,
            'description' => $this->description,
            'positioning' => $this->positioning,
            'strengths' => $this->strengths ?? [],
            'weaknesses' => $this->weaknesses ?? [],
            'notes' => $this->notes,
        ];
    }
}
