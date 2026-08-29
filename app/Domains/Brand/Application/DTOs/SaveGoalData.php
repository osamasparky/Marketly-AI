<?php

namespace App\Domains\Brand\Application\DTOs;

readonly class SaveGoalData
{
    public function __construct(
        public string $goalType,
        public string $priority = 'primary',
        public ?string $description = null,
        public array $targetMetrics = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            goalType: $data['goal_type'] ?? 'lead_generation',
            priority: $data['priority'] ?? 'primary',
            description: $data['description'] ?? null,
            targetMetrics: $data['target_metrics'] ?? []
        );
    }
}
