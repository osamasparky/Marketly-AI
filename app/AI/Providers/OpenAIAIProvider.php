<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIProviderInterface;
use App\AI\Contracts\DTOs\AIStructuredOutput;
use App\AI\Contracts\DTOs\GenerationUsage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OpenAIAIProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $model = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.openai.api_key', '');
        $this->model = $model ?? (string) config('services.openai.model', 'gpt-4o-mini');
        $this->baseUrl = $baseUrl ?? 'https://api.openai.com/v1';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    private function httpClient(int $timeout = 60): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ]);

        if (app()->environment('local', 'testing') || config('app.debug') || empty(ini_get('curl.cainfo'))) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    public function generateText(string $prompt, array $options = []): AIStructuredOutput
    {
        if (!$this->isConfigured()) {
            return new AIStructuredOutput(
                success: false,
                data: [],
                errorMessage: 'OpenAI API key is not configured.'
            );
        }

        $startTime = microtime(true);
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2048;

        $messages = [];
        if (!empty($options['system'])) {
            $messages[] = ['role' => 'system', 'content' => (string) $options['system']];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        try {
            $response = $this->httpClient(45)->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => (float) $temperature,
                'max_tokens' => (int) $maxTokens,
            ]);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? $response->body();
                Log::warning('OpenAI API generateText failed', ['status' => $response->status(), 'error' => $errorMsg]);

                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: "OpenAI API Error ({$response->status()}): {$errorMsg}"
                );
            }

            $json = $response->json();
            $text = $json['choices'][0]['message']['content'] ?? '';
            $usage = $this->extractUsage($json, $latencyMs);

            return new AIStructuredOutput(
                success: true,
                data: ['text' => $text],
                rawText: $text,
                usage: $usage
            );
        } catch (Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('OpenAI API generateText exception', ['error' => $e->getMessage()]);

            return new AIStructuredOutput(
                success: false,
                data: [],
                usage: new GenerationUsage(latencyMs: $latencyMs),
                errorMessage: $e->getMessage()
            );
        }
    }

    public function generateStructured(string $prompt, array $schema, array $options = []): AIStructuredOutput
    {
        if (!$this->isConfigured()) {
            return new AIStructuredOutput(
                success: false,
                data: [],
                errorMessage: 'OpenAI API key is not configured.'
            );
        }

        $startTime = microtime(true);
        $temperature = $options['temperature'] ?? 0.4;
        $maxTokens = $options['max_tokens'] ?? 4096;

        $messages = [];
        $systemPrompt = $options['system'] ?? 'You are an expert AI marketing strategist. You must respond ONLY with valid JSON conforming to the requested schema. Do not wrap with markdown backticks.';
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        $messages[] = ['role' => 'user', 'content' => $prompt];

        try {
            $response = $this->httpClient(60)->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => (float) $temperature,
                'max_tokens' => (int) $maxTokens,
                'response_format' => ['type' => 'json_object'],
            ]);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? $response->body();
                Log::warning('OpenAI API generateStructured failed', ['status' => $response->status(), 'error' => $errorMsg]);

                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: "OpenAI API Error ({$response->status()}): {$errorMsg}"
                );
            }

            $json = $response->json();
            $rawText = $json['choices'][0]['message']['content'] ?? '';
            $usage = $this->extractUsage($json, $latencyMs);

            $parsedData = json_decode($rawText, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try clean backticks if any
                $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
                $cleaned = preg_replace('/\s*```$/', '', $cleaned);
                $parsedData = json_decode($cleaned, true);
            }

            if (empty($parsedData) || !is_array($parsedData)) {
                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    rawText: $rawText,
                    usage: $usage,
                    errorMessage: 'Failed to parse JSON response from OpenAI model.'
                );
            }

            return new AIStructuredOutput(
                success: true,
                data: $parsedData,
                rawText: $rawText,
                usage: $usage
            );
        } catch (Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('OpenAI API generateStructured exception', ['error' => $e->getMessage()]);

            return new AIStructuredOutput(
                success: false,
                data: [],
                usage: new GenerationUsage(latencyMs: $latencyMs),
                errorMessage: $e->getMessage()
            );
        }
    }

    public function analyzeImage(string $imagePathOrUrl, string $prompt, array $options = []): AIStructuredOutput
    {
        if (!$this->isConfigured()) {
            return new AIStructuredOutput(
                success: false,
                data: [],
                errorMessage: 'OpenAI API key is not configured.'
            );
        }

        $startTime = microtime(true);

        try {
            $imageUrl = $imagePathOrUrl;
            if (file_exists($imagePathOrUrl)) {
                $mimeType = mime_content_type($imagePathOrUrl) ?: 'image/jpeg';
                $base64 = base64_encode(file_get_contents($imagePathOrUrl));
                $imageUrl = "data:{$mimeType};base64,{$base64}";
            }

            $response = $this->httpClient(45)->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                        ],
                    ],
                ],
            ]);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: 'OpenAI Image Analysis failed: ' . $response->body()
                );
            }

            $json = $response->json();
            $text = $json['choices'][0]['message']['content'] ?? '';
            $usage = $this->extractUsage($json, $latencyMs);

            return new AIStructuredOutput(
                success: true,
                data: ['analysis' => $text],
                rawText: $text,
                usage: $usage
            );
        } catch (Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            return new AIStructuredOutput(
                success: false,
                data: [],
                usage: new GenerationUsage(latencyMs: $latencyMs),
                errorMessage: $e->getMessage()
            );
        }
    }

    public function analyzeDocument(string $documentPath, string $prompt, array $options = []): AIStructuredOutput
    {
        $content = file_exists($documentPath) ? file_get_contents($documentPath) : $documentPath;
        $combinedPrompt = "DOCUMENT CONTENT:\n" . substr($content, 0, 50000) . "\n\nINSTRUCTIONS:\n" . $prompt;

        return $this->generateText($combinedPrompt, $options);
    }

    public function callWithTools(string $prompt, array $tools, array $options = []): AIStructuredOutput
    {
        return $this->generateText($prompt, $options);
    }

    public function generateImage(string $prompt, array $options = []): AIStructuredOutput
    {
        if (!$this->isConfigured()) {
            return new AIStructuredOutput(
                success: false,
                data: [],
                errorMessage: 'OpenAI API key is not configured.'
            );
        }

        $startTime = microtime(true);
        $aspectRatio = $options['aspect_ratio'] ?? '1:1';
        $orgId = $options['org_id'] ?? 'default';
        $brandId = $options['brand_id'] ?? 'default';
        $imageModel = $options['image_model'] ?? config('services.openai.image_model', 'gpt-image-1');

        $size = '1024x1024';
        if ($aspectRatio === '16:9' || $aspectRatio === '1.91:1') {
            $size = '1536x1024';
        } elseif ($aspectRatio === '9:16' || $aspectRatio === '4:5') {
            $size = '1024x1536';
        }

        try {
            $response = $this->httpClient(60)->post("{$this->baseUrl}/images/generations", [
                'model' => $imageModel,
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
            ]);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? $response->body();
                Log::error('OpenAI Image Generation failed', ['status' => $response->status(), 'error' => $errorMsg]);

                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: "OpenAI Image Error ({$response->status()}): {$errorMsg}"
                );
            }

            $json = $response->json();
            $imageBytes = null;
            $mimeType = 'image/png';

            if (!empty($json['data'][0]['b64_json'])) {
                $imageBytes = base64_decode($json['data'][0]['b64_json']);
            } elseif (!empty($json['data'][0]['url'])) {
                $imgHttp = Http::timeout(30);
                if (app()->environment('local', 'testing') || config('app.debug')) {
                    $imgHttp = $imgHttp->withoutVerifying();
                }
                $imgRes = $imgHttp->get($json['data'][0]['url']);
                if ($imgRes->successful()) {
                    $imageBytes = $imgRes->body();
                    $mimeType = $imgRes->header('Content-Type') ?: 'image/png';
                }
            }

            if (empty($imageBytes)) {
                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: 'No image data returned from OpenAI Image generation.'
                );
            }

            $ext = str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg') ? 'jpg' : 'png';
            $fileName = 'asset_' . uniqid() . '_' . str_replace(':', 'x', $aspectRatio) . '.' . $ext;
            $storageDir = "creative-assets/{$orgId}/{$brandId}";
            $storagePath = "{$storageDir}/{$fileName}";

            Storage::disk('public')->put($storagePath, $imageBytes);
            $publicUrl = Storage::disk('public')->url($storagePath);

            return new AIStructuredOutput(
                success: true,
                data: [
                    'file_name' => $fileName,
                    'file_path' => $storagePath,
                    'image_url' => $publicUrl,
                    'mime_type' => $mimeType,
                    'file_size_bytes' => strlen($imageBytes),
                    'aspect_ratio' => $aspectRatio,
                    'mode' => 'ai_generated',
                    'prompt' => $prompt,
                ],
                usage: new GenerationUsage(latencyMs: $latencyMs, meta: ['model' => $imageModel])
            );
        } catch (Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('OpenAI generateImage exception', ['error' => $e->getMessage()]);

            return new AIStructuredOutput(
                success: false,
                data: [],
                usage: new GenerationUsage(latencyMs: $latencyMs),
                errorMessage: $e->getMessage()
            );
        }
    }

    private function extractUsage(array $json, int $latencyMs): GenerationUsage
    {
        $usage = $json['usage'] ?? [];
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $totalTokens = (int) ($usage['total_tokens'] ?? ($promptTokens + $completionTokens));

        $estimatedCost = ($promptTokens * 0.00000015) + ($completionTokens * 0.00000060);

        return new GenerationUsage(
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: $totalTokens,
            estimatedCost: round($estimatedCost, 6),
            latencyMs: $latencyMs,
            meta: ['model' => $this->model]
        );
    }
}
