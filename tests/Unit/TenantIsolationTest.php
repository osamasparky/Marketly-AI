<?php

namespace Tests\Unit;

use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_tenant_can_access_own_resource(): void
    {
        $context = new TenantContext(organizationId: 101, brandId: 1, role: 'admin');

        // Should execute without throwing exception
        TenantIsolationGuard::assertTenantAccess(resourceTenantId: 101, context: $context);
        $this->assertTrue(true);
    }

    public function test_tenant_cannot_access_other_tenant_resource_with_no_info_disclosure(): void
    {
        $context = new TenantContext(organizationId: 101, brandId: 1, role: 'owner');

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
        $context = new TenantContext(organizationId: 101, brandId: 1, role: 'viewer');

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You are not authorized to access this resource.');

        TenantIsolationGuard::assertPermission($context, 'content.create');
    }

    public function test_editor_can_create_content_but_cannot_publish_or_manage_billing(): void
    {
        $context = new TenantContext(organizationId: 101, brandId: 1, role: 'editor');

        // Editor CAN create content
        TenantIsolationGuard::assertPermission($context, 'content.create');
        $this->assertTrue($context->hasPermission('content.create'));

        // Editor CANNOT publish
        $this->assertFalse($context->hasPermission('social.publish'));

        // Editor CANNOT manage billing
        $this->assertFalse($context->hasPermission('billing.manage'));
    }

    public function test_admin_and_owner_have_appropriate_permissions(): void
    {
        $adminContext = new TenantContext(organizationId: 101, brandId: 1, role: 'admin');
        $ownerContext = new TenantContext(organizationId: 101, brandId: 1, role: 'owner');

        $this->assertTrue($adminContext->hasPermission('social.publish'));
        $this->assertFalse($adminContext->hasPermission('billing.manage')); // Admin cannot manage billing

        $this->assertTrue($ownerContext->hasPermission('social.publish'));
        $this->assertTrue($ownerContext->hasPermission('billing.manage')); // Owner has full billing access
    }
}
