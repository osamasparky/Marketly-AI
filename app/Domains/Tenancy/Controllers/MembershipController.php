<?php

namespace App\Domains\Tenancy\Controllers;

use App\Domains\Tenancy\Application\Services\MembershipApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function __construct(
        private readonly MembershipApplicationService $membershipService
    ) {}

    private function getContext(Request $request): TenantContext
    {
        $context = $request->attributes->get('tenant_context') ?? (app()->bound(TenantContext::class) ? app(TenantContext::class) : null);
        if (!$context) {
            throw new AuthorizationException('You are not authorized to access this resource.');
        }
        return $context;
    }

    /**
     * List members of an organization.
     */
    public function index(Request $request, int $organizationId): JsonResponse
    {
        $context = $this->getContext($request);
        $members = $this->membershipService->listMembers($organizationId, $context);

        return ApiResponse::success(
            data: ['members' => $members],
            meta: ['message' => 'Members retrieved successfully.']
        );
    }

    /**
     * Invite a new member.
     */
    public function invite(Request $request, int $organizationId): JsonResponse
    {
        $context = $this->getContext($request);

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|string|in:owner,admin,manager,editor,viewer',
        ]);

        $invitation = $this->membershipService->inviteMember(
            inviter: $request->user(),
            organizationId: $organizationId,
            email: $validated['email'],
            roleSlug: $validated['role'],
            context: $context
        );

        return ApiResponse::success(
            data: ['invitation' => $invitation],
            meta: ['message' => 'Invitation sent successfully.'],
            status: 201
        );
    }

    /**
     * Accept invitation.
     */
    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|min:32',
        ]);

        $membership = $this->membershipService->acceptInvitation(
            user: $request->user(),
            rawToken: $validated['token']
        );

        return ApiResponse::success(
            data: ['membership' => $membership],
            meta: ['message' => 'Invitation accepted successfully.']
        );
    }

    /**
     * Update a member's role.
     */
    public function updateRole(Request $request, int $organizationId, int $userId): JsonResponse
    {
        $context = $this->getContext($request);

        $validated = $request->validate([
            'role' => 'required|string|in:owner,admin,manager,editor,viewer',
        ]);

        $membership = $this->membershipService->changeMemberRole(
            organizationId: $organizationId,
            targetUserId: $userId,
            newRoleSlug: $validated['role'],
            context: $context
        );

        return ApiResponse::success(
            data: ['membership' => $membership],
            meta: ['message' => 'Member role updated successfully.']
        );
    }

    /**
     * Remove a member from the organization.
     */
    public function destroy(Request $request, int $organizationId, int $userId): JsonResponse
    {
        $context = $this->getContext($request);

        $this->membershipService->removeMember(
            organizationId: $organizationId,
            targetUserId: $userId,
            context: $context
        );

        return ApiResponse::success(
            data: ['removed' => true],
            meta: ['message' => 'Member removed from organization successfully.']
        );
    }
}
