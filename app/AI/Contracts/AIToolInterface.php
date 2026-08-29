<?php

namespace App\AI\Contracts;

use App\AI\Contracts\DTOs\ToolCall;

interface AIToolInterface
{
    /**
     * Unique machine-readable tool identifier.
     */
    public function name(): string;

    /**
     * Tool description and schema specification for model function calling.
     */
    public function definition(): array;

    /**
     * Execute tool under verified tenant context and caller authorization.
     *
     * @param array<string, mixed> $arguments
     * @param int $organizationId
     * @param int $userId
     * @return array<string, mixed>
     */
    public function execute(array $arguments, int $organizationId, int $userId): array;
}
