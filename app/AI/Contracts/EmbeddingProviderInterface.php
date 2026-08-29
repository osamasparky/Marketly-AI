<?php

namespace App\AI\Contracts;

interface EmbeddingProviderInterface
{
    /**
     * Generate numerical vector embedding for text.
     *
     * @param string $text
     * @param array<string, mixed> $options
     * @return array<int, float>
     */
    public function embed(string $text, array $options = []): array;

    /**
     * Search semantic similarity among vectors.
     *
     * @param array<int, float> $queryVector
     * @param array<int, array<string, mixed>> $corpus
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function search(array $queryVector, array $corpus, int $limit = 5): array;
}
