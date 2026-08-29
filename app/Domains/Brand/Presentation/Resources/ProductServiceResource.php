<?php

namespace App\Domains\Brand\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'category' => $this->category,
            'price' => $this->price,
            'currency' => $this->currency,
            'url' => $this->url,
            'features' => $this->features ?? [],
            'status' => $this->status,
        ];
    }
}
