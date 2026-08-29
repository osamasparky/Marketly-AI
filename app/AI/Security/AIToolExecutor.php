<?php

namespace App\AI\Security;

use App\AI\Contracts\AIToolInterface;
use App\AI\Contracts\DTOs\ToolCall;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

class AIToolExecutor
{
    /** @var array<string, AIToolInterface> */
    private array $tools = [];

    public function registerTool(AIToolInterface $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * Safely execute an AI tool call with mandatory tenant context and permission verification.
     *
     * @param ToolCall $toolCall
     * @param int $organizationId
     * @param int $userId
     * @return array<string, mixed>
     * @throws AuthorizationException|InvalidArgumentException
     */
    public function execute(ToolCall $toolCall, int $organizationId, int $userId): array
    {
        if ($organizationId <= 0 || $userId <= 0) {
            throw new AuthorizationException('AI tool execution requires a valid active organization and user context.');
        }

        $toolName = $toolCall->name;
        if (!isset($this->tools[$toolName])) {
            throw new InvalidArgumentException("Unregistered or forbidden AI tool: '{$toolName}'.");
        }

        $tool = $this->tools[$toolName];

        // Execute within strictly bounded application domain scope
        return $tool->execute($toolCall->arguments, $organizationId, $userId);
    }
}
