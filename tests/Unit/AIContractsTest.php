<?php

namespace Tests\Unit;

use App\AI\Contracts\DTOs\AIStructuredOutput;
use App\AI\Contracts\DTOs\GenerationUsage;
use App\AI\Contracts\DTOs\ToolCall;
use App\Social\Contracts\DTOs\PublishPayload;
use App\Social\Contracts\DTOs\PublishResult;
use PHPUnit\Framework\TestCase;

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
