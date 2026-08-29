<?php

namespace App\Domains\Tenancy\Application\Services;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationInvitationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationMembershipModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MembershipApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService
    ) {}

    /**
     * List members of the organization with roles.
     */
    public function listMembers(int $organizationId, TenantContext $context): array
    {
        TenantIsolationGuard::assertTenantAccess($organizationId, $context);
        TenantIsolationGuard::assertPermission($context, 'members.view');

        $memberships = OrganizationMembershipModel::with(['user', 'role'])
            ->where('organization_id', $organizationId)
            ->get();

        return $memberships->map(function (OrganizationMembershipModel $m) {
            return [
                'membership_id' => $m->id,
                'user_id' => $m->user->id,
                'name' => $m->user->name,
                'email' => $m->user->email,
                'role' => $m->role->slug,
                'role_name' => $m->role->name,
                'status' => $m->status,
                'joined_at' => $m->joined_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Invite a new member to the organization with a secure cryptographically random token.
     */
    public function inviteMember(
        UserModel $inviter,
        int $organizationId,
        string $email,
        string $roleSlug,
        TenantContext $context
    ): array {
        TenantIsolationGuard::assertTenantAccess($organizationId, $context);
        TenantIsolationGuard::assertPermission($context, 'members.invite');

        $sanitizedEmail = strtolower(trim($email));
        if (!filter_var($sanitizedEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address format.');
        }

        // Prevent inviting existing active members
        $existingMember = OrganizationMembershipModel::where('organization_id', $organizationId)
            ->whereHas('user', fn($q) => $q->where('email', $sanitizedEmail))
            ->exists();

        if ($existingMember) {
            throw new InvalidArgumentException('User is already a member of this organization.');
        }

        $role = RoleModel::where('slug', $roleSlug)->firstOrFail();

        // Generate high-entropy 64-character token
        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = now()->addDays(7);

        $invitation = OrganizationInvitationModel::create([
            'organization_id' => $organizationId,
            'email' => $sanitizedEmail,
            'role_id' => $role->id,
            'token_hash' => $tokenHash,
            'status' => 'pending',
            'invited_by' => $inviter->id,
            'expires_at' => $expiresAt,
        ]);

        $this->auditService->log(
            action: 'member.invited',
            organizationId: $organizationId,
            userId: $inviter->id,
            entityType: 'organization_invitation',
            entityId: (string) $invitation->id,
            metadata: ['email' => $sanitizedEmail, 'role' => $roleSlug]
        );

        return [
            'invitation_id' => $invitation->id,
            'email' => $sanitizedEmail,
            'role' => $roleSlug,
            'raw_token' => $rawToken,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Accept an organization invitation using the raw token.
     */
    public function acceptInvitation(UserModel $user, string $rawToken): OrganizationMembershipModel
    {
        $tokenHash = hash('sha256', trim($rawToken));

        $invitation = OrganizationInvitationModel::where('token_hash', $tokenHash)
            ->where('status', 'pending')
            ->first();

        if (!$invitation || $invitation->isExpired()) {
            throw new InvalidArgumentException('Invalid, revoked, or expired invitation token.');
        }

        return DB::transaction(function () use ($user, $invitation) {
            // Check if user already joined meanwhile
            $existing = OrganizationMembershipModel::where('organization_id', $invitation->organization_id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                $invitation->update(['status' => 'accepted']);
                $user->update(['current_organization_id' => $invitation->organization_id]);
                return $existing;
            }

            $membership = OrganizationMembershipModel::create([
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
                'role_id' => $invitation->role_id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $invitation->update(['status' => 'accepted']);
            $user->update(['current_organization_id' => $invitation->organization_id]);

            $this->auditService->log(
                action: 'member.accepted',
                organizationId: $invitation->organization_id,
                userId: $user->id,
                entityType: 'organization_membership',
                entityId: (string) $membership->id
            );

            return $membership;
        });
    }

    /**
     * Change member role with strict last-owner protection.
     */
    public function changeMemberRole(
        int $organizationId,
        int $targetUserId,
        string $newRoleSlug,
        TenantContext $context
    ): OrganizationMembershipModel {
        TenantIsolationGuard::assertTenantAccess($organizationId, $context);
        TenantIsolationGuard::assertPermission($context, 'members.update');

        $membership = OrganizationMembershipModel::where('organization_id', $organizationId)
            ->where('user_id', $targetUserId)
            ->firstOrFail();

        $ownerRole = RoleModel::where('slug', 'owner')->firstOrFail();
        $newRole = RoleModel::where('slug', $newRoleSlug)->firstOrFail();

        // Last-owner protection: cannot demote the only owner
        if ($membership->role_id === $ownerRole->id && $newRole->id !== $ownerRole->id) {
            $ownerCount = OrganizationMembershipModel::where('organization_id', $organizationId)
                ->where('role_id', $ownerRole->id)
                ->where('status', 'active')
                ->count();

            if ($ownerCount <= 1) {
                throw new AuthorizationException('Cannot change the role of the last remaining organization owner.');
            }
        }

        $membership->update(['role_id' => $newRole->id]);

        $this->auditService->log(
            action: 'member.role_changed',
            organizationId: $organizationId,
            entityType: 'organization_membership',
            entityId: (string) $membership->id,
            metadata: ['target_user_id' => $targetUserId, 'new_role' => $newRoleSlug]
        );

        return $membership;
    }

    /**
     * Remove a member from the organization with strict last-owner protection.
     */
    public function removeMember(
        int $organizationId,
        int $targetUserId,
        TenantContext $context
    ): bool {
        TenantIsolationGuard::assertTenantAccess($organizationId, $context);
        TenantIsolationGuard::assertPermission($context, 'members.remove');

        $membership = OrganizationMembershipModel::where('organization_id', $organizationId)
            ->where('user_id', $targetUserId)
            ->firstOrFail();

        $ownerRole = RoleModel::where('slug', 'owner')->firstOrFail();

        // Last-owner protection: cannot delete the only owner
        if ($membership->role_id === $ownerRole->id) {
            $ownerCount = OrganizationMembershipModel::where('organization_id', $organizationId)
                ->where('role_id', $ownerRole->id)
                ->where('status', 'active')
                ->count();

            if ($ownerCount <= 1) {
                throw new AuthorizationException('Cannot remove the last remaining organization owner.');
            }
        }

        $membership->delete();

        $this->auditService->log(
            action: 'member.removed',
            organizationId: $organizationId,
            entityType: 'organization_membership',
            entityId: (string) $membership->id,
            metadata: ['target_user_id' => $targetUserId]
        );

        return true;
    }
}
