<?php

namespace App\AI\Contracts;

interface ImageProviderInterface
{
    /**
     * Generate visual image based on prompt and aspect ratio.
     *
     * @param string $prompt
     * @param string $aspectRatio (1:1, 4:5, 9:16, 16:9)
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generate(string $prompt, string $aspectRatio = '1:1', array $options = []): array;

    /**
     * Edit or iterate existing image with mask/prompt.
     *
     * @param string $sourceImagePath
     * @param string $editPrompt
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function edit(string $sourceImagePath, string $editPrompt, array $options = []): array;

    /**
     * Validate generated image quality and adherence.
     *
     * @param string $imagePath
     * @param array<string, mixed> $criteria
     * @return bool
     */
    public function validate(string $imagePath, array $criteria = []): bool;
}
