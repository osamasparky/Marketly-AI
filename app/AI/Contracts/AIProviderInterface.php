<?php

namespace App\AI\Contracts;

use App\AI\Contracts\DTOs\AIStructuredOutput;

interface AIProviderInterface
{
    /**
     * Generate freeform text response.
     *
     * @param string $prompt
     * @param array<string, mixed> $options
     * @return AIStructuredOutput
     */
    public function generateText(string $prompt, array $options = []): AIStructuredOutput;

    /**
     * Generate schema-validated structured JSON output.
     *
     * @param string $prompt
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $options
     * @return AIStructuredOutput
     */
    public function generateStructured(string $prompt, array $schema, array $options = []): AIStructuredOutput;

    /**
     * Analyze image content with instructions.
     *
     * @param string $imagePathOrUrl
     * @param string $prompt
     * @param array<string, mixed> $options
     * @return AIStructuredOutput
     */
    public function analyzeImage(string $imagePathOrUrl, string $prompt, array $options = []): AIStructuredOutput;

    /**
     * Analyze document content (PDF/Text/Docx).
     *
     * @param string $documentPath
     * @param string $prompt
     * @param array<string, mixed> $options
     * @return AIStructuredOutput
     */
    public function analyzeDocument(string $documentPath, string $prompt, array $options = []): AIStructuredOutput;

    /**
     * Execute model with tool/function definitions.
     *
     * @param string $prompt
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return AIStructuredOutput
     */
    public function callWithTools(string $prompt, array $tools, array $options = []): AIStructuredOutput;
}
