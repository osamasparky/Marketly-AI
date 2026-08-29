<?php

namespace App\AI\Contracts\DTOs;

class AIStructuredOutput
{
    public function __construct(
        public readonly bool $success,
        public readonly array $data,
        public readonly ?string $rawText = null,
        public readonly ?GenerationUsage $usage = null,
        public readonly array $toolCalls = [],
        public readonly ?string $errorMessage = null
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'raw_text' => $this->rawText,
            'usage' => $this->usage?->toArray(),
            'tool_calls' => array_map(fn(ToolCall $tc) => $tc->toArray(), $this->toolCalls),
            'error_message' => $this->errorMessage,
        ];
    }
}
