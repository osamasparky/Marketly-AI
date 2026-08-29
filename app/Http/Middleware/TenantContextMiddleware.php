<?php

namespace App\Http\Middleware;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Shared\Enums\UserRole;
use App\Domains\Tenancy\Application\Services\AuthorizationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationMembershipModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantContextMiddleware
{
    public function __construct(
        private readonly AuthorizationService $authService
    ) {}

    /**
     * Handle incoming request and resolve authenticated Tenant Context server-side.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Always reset stale container instance
        app()->forgetInstance(TenantContext::class);

        /** @var UserModel|null $user */
        $user = $request->user();

        if ($user) {
            $context = $this->resolveTenantContext($user, $request);
            if ($context) {
                app()->instance(TenantContext::class, $context);
                $request->attributes->set('tenant_context', $context);
            }
        }

        return $next($request);
    }

    private function resolveTenantContext(UserModel $user, Request $request): ?TenantContext
    {
        // Routes that do not require an active tenant context before execution (e.g. accepting invitations)
        if ($request->is('api/v1/invitations/accept') || $request->is('*/invitations/accept')) {
            return null;
        }

        // 1. Check route parameter '{organization}'
        $routeOrg = $request->route('organization');
        $routeOrgId = is_numeric($routeOrg) ? (int) $routeOrg : ($routeOrg instanceof OrganizationModel ? $routeOrg->id : null);

        // 2. Check explicit header 'X-Organization-Id'
        $headerOrgId = $request->header('X-Organization-Id');
        
        $explicitOrgId = $routeOrgId ?: ($headerOrgId ? (int) $headerOrgId : null);

        // 3. Query verified active membership
        $membershipQuery = OrganizationMembershipModel::with('role.permissions')
            ->where('user_id', $user->id)
            ->where('status', 'active');

        if ($explicitOrgId) {
            $membership = (clone $membershipQuery)->where('organization_id', $explicitOrgId)->first();
            if (!$membership) {
                throw new AuthorizationException('You are not authorized to access this resource.');
            }
        } else {
            $targetId = $user->current_organization_id;
            $membership = $targetId
                ? (clone $membershipQuery)->where('organization_id', $targetId)->first()
                : $membershipQuery->first();
        }

        if (!$membership) {
            return null;
        }

        $roleSlug = $membership->role->slug;
        $userRole = UserRole::tryFrom($roleSlug) ?? UserRole::VIEWER;
        $permissions = $this->authService->getPermissions($user->id, $membership->organization_id);

        return new TenantContext(
            userId: $user->id,
            organizationId: $membership->organization_id,
            role: $userRole,
            permissions: $permissions
        );
    }
}
