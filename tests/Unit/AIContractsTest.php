<?php

namespace Tests\Unit;

use App\AI\Contracts\AIProviderInterface;
use App\AI\Contracts\DTOs\AIStructuredOutput;
use App\AI\Contracts\DTOs\GenerationUsage;
use App\AI\Contracts\DTOs\ToolCall;
use App\AI\Providers\GeminiAIProvider;
use App\Domains\Content\Domain\Services\ContentGeneratorAgent;
use App\Social\Contracts\DTOs\PublishPayload;
use App\Social\Contracts\DTOs\PublishResult;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIContractsTest extends TestCase
{
    public function test_ai_structured_output_dto(): void
    {
        $usage = new GenerationUsage(100, 50, 150, 0.00045, 320);
        $toolCall = new ToolCall('tc_1', 'createCampaign', ['name' => 'Summer Sale']);
        $output = new AIStructuredOutput(
            success: true,
            data: ['campaign' => 'Summer Sale', 'posts_count' => 7],
            rawText: '{"campaign": "Summer Sale"}',
            usage: $usage,
            toolCalls: [$toolCall]
        );

        $array = $output->toArray();

        $this->assertTrue($array['success']);
        $this->assertEquals('Summer Sale', $array['data']['campaign']);
        $this->assertEquals(150, $array['usage']['total_tokens']);
        $this->assertCount(1, $array['tool_calls']);
        $this->assertEquals('createCampaign', $array['tool_calls'][0]['name']);
    }

    public function test_ai_provider_container_binding(): void
    {
        $provider = app(AIProviderInterface::class);
        $this->assertInstanceOf(GeminiAIProvider::class, $provider);
    }

    public function test_gemini_provider_unconfigured_fallback(): void
    {
        $provider = new GeminiAIProvider(apiKey: '');
        $this->assertFalse($provider->isConfigured());

        $textRes = $provider->generateText('Hello');
        $this->assertFalse($textRes->success);
        $this->assertStringContainsString('not configured', $textRes->errorMessage);

        $structRes = $provider->generateStructured('Hello', []);
        $this->assertFalse($structRes->success);
    }

    public function test_gemini_provider_mocked_success(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"title":"Smart AI Post","hook":"Hook line","caption":"A detailed caption with value and insights.","cta":"Click here","hashtags":["#AI","#Marketing"],"visual_brief":{"type":"card","description":"Visual card"}}']
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 120,
                    'candidatesTokenCount' => 60,
                    'totalTokenCount' => 180,
                ]
            ], 200)
        ]);

        $provider = new GeminiAIProvider(apiKey: 'fake_key_123', model: 'gemini-2.0-flash');
        $this->assertTrue($provider->isConfigured());

        $result = $provider->generateStructured('Generate post', []);
        $this->assertTrue($result->success);
        $this->assertEquals('Smart AI Post', $result->data['title']);
        $this->assertEquals(180, $result->usage->totalTokens);
    }

    public function test_content_generator_agent_with_ai_and_fallback(): void
    {
        // 1. Fallback when AI is null / unconfigured
        $unconfiguredAgent = new ContentGeneratorAgent(new GeminiAIProvider(apiKey: ''));
        $fallbackRes = $unconfiguredAgent->generate([
            'brand' => ['business_name' => 'Acme Test', 'industry' => 'Tech'],
            'generation_parameters' => ['language' => 'ar', 'platform' => 'linkedin']
        ]);
        $this->assertNotEmpty($fallbackRes['caption']);
        $this->assertNotEmpty($fallbackRes['hook']);

        // 2. Real AI path when provider succeeds
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode([
                                    'title' => 'Custom AI Generated Strategy',
                                    'hook' => 'Exclusive hook from real AI model',
                                    'caption' => 'This is an authentic post generated directly by Gemini AI model with deep insights.',
                                    'cta' => 'Try Marketly today!',
                                    'hashtags' => ['#MarketlyAI', '#Growth'],
                                    'visual_brief' => [
                                        'type' => 'card_graphic',
                                        'description' => 'Dynamic AI card',
                                    ]
                                ])]
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => ['promptTokenCount' => 50, 'candidatesTokenCount' => 50, 'totalTokenCount' => 100]
            ], 200)
        ]);

        $aiAgent = new ContentGeneratorAgent(new GeminiAIProvider(apiKey: 'live_test_key'));
        $aiRes = $aiAgent->generate([
            'brand' => ['business_name' => 'Acme Test', 'industry' => 'Tech'],
            'generation_parameters' => ['language' => 'ar', 'platform' => 'linkedin']
        ]);

        $this->assertEquals('Custom AI Generated Strategy', $aiRes['title']);
        $this->assertEquals('Exclusive hook from real AI model', $aiRes['hook']);
    }

    public function test_social_publish_dtos(): void
    {
        $payload = new PublishPayload(
            contentPostId: 'post_123',
            text: 'Introducing Marketly-AI!',
            mediaUrls: ['https://example.com/banner.jpg'],
            mediaType: 'image',
            platformOptions: ['page_id' => 'fb_page_456'],
            idempotencyKey: 'idem_key_789'
        );

        $result = new PublishResult(
            success: true,
            externalPostId: 'ext_fb_999',
            externalPostUrl: 'https://facebook.com/posts/999',
            rawResponse: ['id' => 'ext_fb_999']
        );

        $this->assertEquals('post_123', $payload->contentPostId);
        $this->assertEquals('idem_key_789', $payload->idempotencyKey);
        $this->assertTrue($result->success);
        $this->assertEquals('ext_fb_999', $result->externalPostId);
    }
}
