<?php

namespace Tests\Unit;

use App\AI\Contracts\AIToolInterface;
use App\AI\Contracts\DTOs\ToolCall;
use App\AI\Security\AISchemaValidator;
use App\AI\Security\AIToolExecutor;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

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

    public function test_ai_schema_validator_enforces_required_fields(): void
    {
        $output = ['title' => 'Launch Post'];
        $schema = ['title' => 'string', 'hook' => 'string'];

        $this->expectException(InvalidArgumentException::class);
        AISchemaValidator::validate($output, $schema);
    }

    public function test_ai_schema_validator_enforces_types(): void
    {
        $output = ['title' => 'Launch Post', 'score' => 'not_a_number'];
        $schema = ['title' => 'string', 'score' => 'integer'];

        $this->expectException(InvalidArgumentException::class);
        AISchemaValidator::validate($output, $schema);
    }

    public function test_ai_schema_validator_accepts_valid_payload(): void
    {
        $output = ['title' => ' Launch Post ', 'score' => 95, 'approved' => true];
        $schema = ['title' => 'string', 'score' => 'integer', 'approved' => 'boolean'];

        $validated = AISchemaValidator::validate($output, $schema);
        $this->assertEquals('Launch Post', $validated['title']);
        $this->assertEquals(95, $validated['score']);
        $this->assertTrue($validated['approved']);
    }
}
