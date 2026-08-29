<?php

namespace Tests\Unit;

use App\Domains\Shared\Enums\UserRole;
use App\Domains\Tenancy\Application\Services\AuthorizationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_tenant_can_access_own_resource(): void
    {
        $context = new TenantContext(userId: 1, organizationId: 101, brandId: 1, role: 'admin', permissions: ['organization.view']);

        // Should execute without throwing exception
        TenantIsolationGuard::assertTenantAccess(resourceTenantId: 101, context: $context);
        $this->assertTrue(true);
    }

    public function test_tenant_cannot_access_other_tenant_resource_with_no_info_disclosure(): void
    {
        $context = new TenantContext(userId: 1, organizationId: 101, brandId: 1, role: 'owner', permissions: []);

        try {
            // Tenant 101 attempting to access resource owned by Tenant 202
            TenantIsolationGuard::assertTenantAccess(resourceTenantId: 202, context: $context);
            $this->fail('Expected AuthorizationException was not thrown');
        } catch (AuthorizationException $e) {
            // Assert message is completely generic with zero internal ID leakage
            $this->assertEquals('You are not authorized to access this resource.', $e->getMessage());
            $this->assertStringNotContainsString('101', $e->getMessage());
            $this->assertStringNotContainsString('202', $e->getMessage());
            $this->assertStringNotContainsString('organization', strtolower($e->getMessage()));
        }
    }

    public function test_viewer_role_cannot_perform_content_creation(): void
    {
        $context = new TenantContext(userId: 1, organizationId: 101, brandId: 1, role: 'viewer', permissions: ['organization.view']);

        $mockAuth = $this->createMock(AuthorizationService::class);
        $mockAuth->expects($this->once())
            ->method('authorize')
            ->with(1, 101, 'content.create')
            ->willThrowException(new AuthorizationException('You are not authorized to access this resource.'));

        $this->app->instance(AuthorizationService::class, $mockAuth);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You are not authorized to access this resource.');

        TenantIsolationGuard::assertPermission($context, 'content.create');
    }

    public function test_editor_can_create_content_but_cannot_publish_or_manage_billing(): void
    {
        $context = new TenantContext(
            userId: 1,
            organizationId: 101,
            brandId: 1,
            role: 'editor',
            permissions: ['content.create', 'content.view']
        );

        $this->assertTrue($context->hasPermission('content.create'));
        $this->assertFalse($context->hasPermission('social.publish'));
        $this->assertFalse($context->hasPermission('billing.manage'));
    }

    public function test_role_labels(): void
    {
        $this->assertEquals('Owner', UserRole::OWNER->label());
        $this->assertEquals('Administrator', UserRole::ADMIN->label());
        $this->assertEquals('Marketing Manager', UserRole::MANAGER->label());
        $this->assertEquals('Content Editor', UserRole::EDITOR->label());
        $this->assertEquals('Viewer', UserRole::VIEWER->label());
    }
}
