<?php

namespace App\Http\Middleware;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Shared\Enums\UserRole;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationMembershipModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantContextMiddleware
{
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
        // 1. Check route parameter '{organization}'
        $routeOrg = $request->route('organization');
        $routeOrgId = is_numeric($routeOrg) ? (int) $routeOrg : ($routeOrg instanceof OrganizationModel ? $routeOrg->id : null);

        // 2. Check explicit header 'X-Organization-Id'
        $headerOrgId = $request->header('X-Organization-Id');
        
        $targetOrgId = $routeOrgId ?: ($headerOrgId ? (int) $headerOrgId : $user->current_organization_id);

        // 3. Query verified active membership
        $membershipQuery = OrganizationMembershipModel::with('role')
            ->where('user_id', $user->id)
            ->where('status', 'active');

        if ($targetOrgId) {
            $membership = (clone $membershipQuery)->where('organization_id', $targetOrgId)->first();
        } else {
            $membership = $membershipQuery->first();
        }

        if (!$membership) {
            return null;
        }

        $roleSlug = $membership->role->slug;
        $userRole = UserRole::tryFrom($roleSlug) ?? UserRole::VIEWER;

        return new TenantContext(
            organizationId: $membership->organization_id,
            role: $userRole
        );
    }
}
