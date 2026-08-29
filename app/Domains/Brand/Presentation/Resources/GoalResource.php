<?php

namespace App\Domains\Brand\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'goal_type' => $this->goal_type,
            'priority' => $this->priority,
            'description' => $this->description,
            'target_metrics' => $this->target_metrics ?? [],
            'status' => $this->status,
        ];
    }
}
