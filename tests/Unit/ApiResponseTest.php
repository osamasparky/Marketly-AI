<?php

namespace Tests\Unit;

use App\Support\ApiResponse;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseTest extends TestCase
{
    public function test_success_response_structure(): void
    {
        $response = ApiResponse::success(['name' => 'Marketly-AI'], ['version' => '1.0']);
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('meta', $content);
        $this->assertEquals('Marketly-AI', $content['data']['name']);
        $this->assertEquals('1.0', $content['meta']['version']);
    }

    public function test_error_response_structure(): void
    {
        $response = ApiResponse::error('Invalid input', 'VALIDATION_FAILED', ['email' => ['Required']], Response::HTTP_UNPROCESSABLE_ENTITY);
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('code', $content);
        $this->assertArrayHasKey('errors', $content);
        $this->assertEquals('Invalid input', $content['message']);
        $this->assertEquals('VALIDATION_FAILED', $content['code']);
        $this->assertArrayHasKey('email', $content['errors']);
    }
}
