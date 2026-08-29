<?php

namespace App\AI\Contracts;

interface AIToolInterface
{
    /**
     * Unique machine-readable tool identifier.
     */
    public function name(): string;

    /**
     * Required domain permission to execute this AI tool (e.g. 'content.create', 'social.publish').
     * If null, base tenant authentication is enforced.
     */
    public function requiredPermission(): ?string;

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
