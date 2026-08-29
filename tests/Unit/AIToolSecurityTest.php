<?php

namespace Tests\Unit;

use App\AI\Contracts\AIToolInterface;
use App\AI\Contracts\DTOs\ToolCall;
use App\AI\Security\AISchemaValidator;
use App\AI\Security\AIToolExecutor;
use App\Domains\Tenancy\Application\Services\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use Tests\TestCase;

class AIToolSecurityTest extends TestCase
{
    public function test_ai_tool_executor_blocks_missing_tenant_context(): void
    {
        $executor = new AIToolExecutor();
        $toolCall = new ToolCall('tc_1', 'createCampaign', ['name' => 'Summer Sale']);

        $this->expectException(AuthorizationException::class);
        $executor->execute($toolCall, organizationId: 0, userId: 1);
    }

    public function test_ai_tool_executor_blocks_unregistered_tools(): void
    {
        $executor = new AIToolExecutor();
        $toolCall = new ToolCall('tc_1', 'arbitraryDangerousTool', []);

        $this->expectException(InvalidArgumentException::class);
        $executor->execute($toolCall, organizationId: 1, userId: 1);
    }

    public function test_ai_tool_executes_when_registered_and_authorized(): void
    {
        $mockTool = new class implements AIToolInterface {
            public function name(): string { return 'mockTool'; }
            public function requiredPermission(): ?string { return null; }
            public function definition(): array { return []; }
            public function execute(array $arguments, int $organizationId, int $userId): array {
                return ['success' => true, 'org' => $organizationId, 'user' => $userId];
            }
        };

        $executor = new AIToolExecutor();
        $executor->registerTool($mockTool);

        $toolCall = new ToolCall('tc_1', 'mockTool', ['key' => 'val']);
        $result = $executor->execute($toolCall, organizationId: 42, userId: 7);

        $this->assertTrue($result['success']);
        $this->assertEquals(42, $result['org']);
        $this->assertEquals(7, $result['user']);
    }

    public function test_ai_tool_enforces_required_permission(): void
    {
        $mockTool = new class implements AIToolInterface {
            public function name(): string { return 'publishPostTool'; }
            public function requiredPermission(): ?string { return 'social.publish'; }
            public function definition(): array { return []; }
            public function execute(array $arguments, int $organizationId, int $userId): array {
                return ['published' => true];
            }
        };

        $mockAuthService = $this->createMock(AuthorizationService::class);
        $mockAuthService->expects($this->once())
            ->method('authorize')
            ->with(7, 42, 'social.publish')
            ->willThrowException(new AuthorizationException('You are not authorized to access this resource.'));

        $executor = new AIToolExecutor($mockAuthService);
        $executor->registerTool($mockTool);

        $this->expectException(AuthorizationException::class);
        $executor->execute(new ToolCall('tc_1', 'publishPostTool', []), organizationId: 42, userId: 7);
    }

    public function test_ai_schema_validator_enforces_required_fields(): void
    {
        $output = ['title' => 'Launch Post'];
        $schema = ['title' => 'string', 'hook' => 'string'];

        $this->expectException(InvalidArgumentException::class);
        AISchemaValidator::validate($output, $schema);
    }

    public function test_ai_schema_validator_enforces_types(): void
    {
        $output = ['title' => 'Launch Post', 'word_count' => 'not_a_number'];
        $schema = ['title' => 'string', 'word_count' => 'integer'];

        $this->expectException(InvalidArgumentException::class);
        AISchemaValidator::validate($output, $schema);
    }

    public function test_ai_schema_validator_accepts_valid_payload(): void
    {
        $output = ['title' => 'Launch Post', 'word_count' => 120];
        $schema = ['title' => 'string', 'word_count' => 'integer'];

        $result = AISchemaValidator::validate($output, $schema);
        $this->assertEquals('Launch Post', $result['title']);
        $this->assertEquals(120, $result['word_count']);
    }
}
