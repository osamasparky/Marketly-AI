<?php

namespace App\AI\Contracts;

interface VideoProviderInterface
{
    /**
     * Start video generation request (returns job reference).
     *
     * @param string $scriptOrPrompt
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generate(string $scriptOrPrompt, array $options = []): array;

    /**
     * Get generation status and progress.
     *
     * @param string $jobId
     * @return array<string, mixed>
     */
    public function getStatus(string $jobId): array;

    /**
     * Download completed video asset.
     *
     * @param string $jobId
     * @param string $destinationPath
     * @return string
     */
    public function download(string $jobId, string $destinationPath): string;
}
