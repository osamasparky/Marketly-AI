<?php

namespace Tests\Unit;

use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_tenant_can_access_own_resource(): void
    {
        $context = new TenantContext(organizationId: 101, brandId: 1, role: 'admin');

        // Should execute without throwing exception
        TenantIsolationGuard::assertTenantAccess(resourceTenantId: 101, context: $context);
        $this->assertTrue(true);
    }

    public function test_tenant_cannot_read_or_access_other_tenant_resource(): void
    {
        $context = new TenantContext(organizationId: 101, brandId: 1, role: 'owner');

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Cross-tenant access forbidden');

        // Tenant 101 attempting to access resource owned by Tenant 202
        TenantIsolationGuard::assertTenantAccess(resourceTenantId: 202, context: $context);
    }

    public function test_viewer_role_cannot_perform_mutations(): void
    {
        $context = new TenantContext(organizationId: 101, brandId: 1, role: 'viewer');

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage("User with role 'viewer' does not have mutation permissions");

        TenantIsolationGuard::assertMutationPermission($context);
    }

    public function test_editor_and_admin_can_perform_mutations(): void
    {
        $editorContext = new TenantContext(organizationId: 101, brandId: 1, role: 'editor');
        $adminContext = new TenantContext(organizationId: 101, brandId: 1, role: 'admin');

        TenantIsolationGuard::assertMutationPermission($editorContext);
        TenantIsolationGuard::assertMutationPermission($adminContext);

        $this->assertTrue($editorContext->canMutate());
        $this->assertTrue($adminContext->canMutate());
    }
}
