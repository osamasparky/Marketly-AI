<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIProviderInterface;
use App\AI\Contracts\DTOs\AIStructuredOutput;
use App\AI\Contracts\DTOs\GenerationUsage;
use App\AI\Contracts\DTOs\ToolCall;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiAIProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $model = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.gemini.api_key', '');
        $this->model = $model ?? (string) config('services.gemini.model', 'gemini-2.0-flash');
        $this->baseUrl = $baseUrl ?? 'https://generativelanguage.googleapis.com/v1beta';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function generateText(string $prompt, array $options = []): AIStructuredOutput
    {
        if (!$this->isConfigured()) {
            return new AIStructuredOutput(
                success: false,
                data: [],
                errorMessage: 'Gemini API key is not configured.'
            );
        }

        $startTime = microtime(true);
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2048;

        try {
            $response = $this->httpClient(45)
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $prompt]],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => (float) $temperature,
                        'maxOutputTokens' => (int) $maxTokens,
                    ],
                ]);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? $response->body();
                Log::warning('Gemini API generateText failed', ['status' => $response->status(), 'error' => $errorMsg]);

                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: "Gemini API Error ({$response->status()}): {$errorMsg}"
                );
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $usage = $this->extractUsage($json, $latencyMs);

            return new AIStructuredOutput(
                success: true,
                data: ['text' => $text],
                rawText: $text,
                usage: $usage
            );
        } catch (Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('Gemini API generateText exception', ['error' => $e->getMessage()]);

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
                errorMessage: 'Gemini API key is not configured.'
            );
        }

        $startTime = microtime(true);
        $temperature = $options['temperature'] ?? 0.4;
        $maxTokens = $options['max_tokens'] ?? 4096;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $prompt . "\n\nIMPORTANT: You must respond ONLY with valid JSON conforming to the requested schema. Do not wrap with markdown backticks."
                        ]
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => (float) $temperature,
                'maxOutputTokens' => (int) $maxTokens,
                'responseMimeType' => 'application/json',
            ],
        ];

        // If a structured schema array is provided, attach responseSchema
        if (!empty($schema)) {
            $payload['generationConfig']['responseSchema'] = $schema;
        }

        // Support dedicated systemInstruction in Gemini API
        if (!empty($options['system'])) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => (string) $options['system']]
                ]
            ];
        }

        try {
            $response = $this->httpClient(45)
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", $payload);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? $response->body();
                Log::warning('Gemini API generateStructured failed', ['status' => $response->status(), 'error' => $errorMsg]);

                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: "Gemini API Error ({$response->status()}): {$errorMsg}"
                );
            }

            $json = $response->json();
            $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $usage = $this->extractUsage($json, $latencyMs);

            // Clean markdown code fence formatting if present
            $cleaned = trim($rawText);
            if (str_starts_with($cleaned, '```json')) {
                $cleaned = substr($cleaned, 7);
            } elseif (str_starts_with($cleaned, '```')) {
                $cleaned = substr($cleaned, 3);
            }
            if (str_ends_with($cleaned, '```')) {
                $cleaned = substr($cleaned, 0, -3);
            }
            $cleaned = trim($cleaned);

            $parsedData = json_decode($cleaned, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsedData)) {
                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    rawText: $rawText,
                    usage: $usage,
                    errorMessage: 'Failed to decode JSON from Gemini: ' . json_last_error_msg()
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
            Log::error('Gemini API generateStructured exception', ['error' => $e->getMessage()]);

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
                errorMessage: 'Gemini API key is not configured.'
            );
        }

        $startTime = microtime(true);

        try {
            $inlineData = [];
            if (filter_var($imagePathOrUrl, FILTER_VALIDATE_URL)) {
                $imgResponse = Http::timeout(15)->get($imagePathOrUrl);
                if ($imgResponse->successful()) {
                    $mime = $imgResponse->header('Content-Type') ?: 'image/jpeg';
                    $inlineData = [
                        'mimeType' => $mime,
                        'data' => base64_encode($imgResponse->body()),
                    ];
                }
            } elseif (file_exists($imagePathOrUrl)) {
                $mime = mime_content_type($imagePathOrUrl) ?: 'image/jpeg';
                $inlineData = [
                    'mimeType' => $mime,
                    'data' => base64_encode(file_get_contents($imagePathOrUrl)),
                ];
            }

            $parts = [['text' => $prompt]];
            if (!empty($inlineData)) {
                $parts[] = ['inlineData' => $inlineData];
            }

            $response = $this->httpClient(45)
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => $parts,
                        ],
                    ],
                ]);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: 'Gemini Image Analysis failed: ' . $response->body()
                );
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
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
        if (!$this->isConfigured()) {
            return new AIStructuredOutput(
                success: false,
                data: [],
                errorMessage: 'Gemini API key is not configured.'
            );
        }

        $startTime = microtime(true);

        try {
            $response = $this->httpClient(45)
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $prompt]],
                        ],
                    ],
                    'tools' => $tools,
                ]);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: 'Gemini callWithTools failed: ' . $response->body()
                );
            }

            $json = $response->json();
            $part = $json['candidates'][0]['content']['parts'][0] ?? [];
            $usage = $this->extractUsage($json, $latencyMs);

            $toolCalls = [];
            if (isset($part['functionCall'])) {
                $toolCalls[] = new ToolCall(
                    id: uniqid('call_'),
                    name: $part['functionCall']['name'] ?? '',
                    arguments: $part['functionCall']['args'] ?? []
                );
            }

            $text = $part['text'] ?? null;

            return new AIStructuredOutput(
                success: true,
                data: ['text' => $text],
                rawText: $text,
                usage: $usage,
                toolCalls: $toolCalls
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

    /**
     * Generate visual marketing image using Gemini Multimodal Image Generation / Imagen.
     */
    public function generateImage(string $prompt, array $options = []): AIStructuredOutput
    {
        if (!$this->isConfigured()) {
            return new AIStructuredOutput(
                success: false,
                data: [],
                errorMessage: 'Gemini API key is not configured.'
            );
        }

        $startTime = microtime(true);
        $aspectRatio = $options['aspect_ratio'] ?? '1:1';
        $orgId = $options['org_id'] ?? 'default';
        $brandId = $options['brand_id'] ?? 'default';

        // 1. Primary Strategy: Gemini Multimodal Generation via generateContent with responseModalities: ["TEXT", "IMAGE"]
        $geminiImageModel = $options['image_model'] ?? config('services.gemini.image_model', 'gemini-2.0-flash-exp');

        try {
            $response = $this->httpClient(60)
                ->post("{$this->baseUrl}/models/{$geminiImageModel}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['TEXT', 'IMAGE'],
                    ],
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $base64Data = null;
                $mimeType = 'image/png';

                $candidates = $json['candidates'] ?? [];
                foreach ($candidates as $candidate) {
                    $parts = $candidate['content']['parts'] ?? [];
                    foreach ($parts as $part) {
                        if (!empty($part['inlineData']['data'])) {
                            $base64Data = $part['inlineData']['data'];
                            $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';
                            break 2;
                        }
                    }
                }

                if (!empty($base64Data)) {
                    $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
                    $imageBytes = base64_decode($base64Data);
                    $ext = str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg') ? 'jpg' : 'png';
                    $fileName = 'asset_' . uniqid() . '_' . str_replace(':', 'x', $aspectRatio) . '.' . $ext;
                    $storageDir = "creative-assets/{$orgId}/{$brandId}";
                    $storagePath = "{$storageDir}/{$fileName}";

                    \Illuminate\Support\Facades\Storage::disk('public')->put($storagePath, $imageBytes);
                    $publicUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($storagePath);

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
                        usage: new GenerationUsage(latencyMs: $latencyMs, meta: ['model' => $geminiImageModel])
                    );
                }
            } else {
                Log::warning('Gemini multimodal image generateContent returned non-200, attempting Imagen fallback', [
                    'status' => $response->status(),
                    'error' => $response->json()['error']['message'] ?? $response->body(),
                    'model' => $geminiImageModel,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini multimodal image generation exception, attempting Imagen fallback', [
                'error' => $e->getMessage(),
                'model' => $geminiImageModel,
            ]);
        }

        // 2. Secondary Strategy: Imagen 3 Predict API (imagen-3.0-generate-002)
        $imagenModel = 'imagen-3.0-generate-002';
        $validAspectRatios = ['1:1', '9:16', '16:9', '4:3', '3:4'];
        $aspectRatioParam = in_array($aspectRatio, $validAspectRatios, true) ? $aspectRatio : '1:1';

        try {
            $response = $this->httpClient(60)
                ->post("{$this->baseUrl}/models/{$imagenModel}:predict?key={$this->apiKey}", [
                    'instances' => [
                        ['prompt' => $prompt],
                    ],
                    'parameters' => [
                        'sampleCount' => 1,
                        'aspectRatio' => $aspectRatioParam,
                        'outputOptions' => [
                            'mimeType' => 'image/jpeg',
                        ],
                    ],
                ]);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? $response->body();
                Log::error('Gemini AI image generation failed across all strategies', [
                    'status' => $response->status(),
                    'error' => $errorMsg,
                    'models_tried' => [$geminiImageModel, $imagenModel],
                ]);

                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: "Google AI Image Error ({$response->status()}): {$errorMsg}"
                );
            }

            $json = $response->json();
            $base64Data = $json['predictions'][0]['bytesBase64Encoded']
                ?? $json['predictions'][0]['image']['bytesBase64Encoded']
                ?? null;

            if (empty($base64Data)) {
                Log::error('Google Imagen API returned successful response without base64 image data', ['response' => $json]);

                return new AIStructuredOutput(
                    success: false,
                    data: [],
                    usage: new GenerationUsage(latencyMs: $latencyMs),
                    errorMessage: 'No image data returned from Google Imagen API.'
                );
            }

            $imageBytes = base64_decode($base64Data);
            $fileName = 'asset_' . uniqid() . '_' . str_replace(':', 'x', $aspectRatio) . '.jpg';
            $storageDir = "creative-assets/{$orgId}/{$brandId}";
            $storagePath = "{$storageDir}/{$fileName}";

            \Illuminate\Support\Facades\Storage::disk('public')->put($storagePath, $imageBytes);
            $publicUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($storagePath);

            return new AIStructuredOutput(
                success: true,
                data: [
                    'file_name' => $fileName,
                    'file_path' => $storagePath,
                    'image_url' => $publicUrl,
                    'mime_type' => 'image/jpeg',
                    'file_size_bytes' => strlen($imageBytes),
                    'aspect_ratio' => $aspectRatio,
                    'mode' => 'ai_generated',
                    'prompt' => $prompt,
                ],
                usage: new GenerationUsage(latencyMs: $latencyMs, meta: ['model' => $imagenModel])
            );
        } catch (\Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('Gemini AI generateImage exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new AIStructuredOutput(
                success: false,
                data: [],
                usage: new GenerationUsage(latencyMs: $latencyMs),
                errorMessage: $e->getMessage()
            );
        }
    }

    private function httpClient(int $timeout = 45): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout($timeout)->withHeaders(['Content-Type' => 'application/json']);

        // In local/testing environments or when running on local OS without CA bundle, disable cURL SSL verification
        if (app()->environment('local', 'testing') || config('app.debug') || empty(ini_get('curl.cainfo'))) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function extractUsage(array $json, int $latencyMs): GenerationUsage
    {
        $usageMeta = $json['usageMetadata'] ?? [];
        $promptTokens = (int) ($usageMeta['promptTokenCount'] ?? 0);
        $completionTokens = (int) ($usageMeta['candidatesTokenCount'] ?? 0);
        $totalTokens = (int) ($usageMeta['totalTokenCount'] ?? ($promptTokens + $completionTokens));

        // Gemini 2.0 Flash approx cost: $0.10 / 1M prompt, $0.40 / 1M completion
        $estimatedCost = ($promptTokens * 0.00000010) + ($completionTokens * 0.00000040);

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
