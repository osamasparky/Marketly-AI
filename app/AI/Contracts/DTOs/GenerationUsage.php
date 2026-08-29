<?php

namespace App\AI\Contracts\DTOs;

class GenerationUsage
{
    public function __construct(
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $totalTokens = 0,
        public readonly float $estimatedCost = 0.0,
        public readonly int $latencyMs = 0,
        public readonly array $meta = []
    ) {}

    public function toArray(): array
    {
        return [
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
            'estimated_cost' => $this->estimatedCost,
            'latency_ms' => $this->latencyMs,
            'meta' => $this->meta,
        ];
    }
}
