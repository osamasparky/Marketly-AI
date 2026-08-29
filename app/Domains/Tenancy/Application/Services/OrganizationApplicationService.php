<?php

namespace App\Domains\Tenancy\Application\Services;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Application\DTOs\UpdateOrganizationData;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationMembershipModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrganizationApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService,
        private readonly AuthorizationService $authService
    ) {}

    /**
     * Create a new organization tenant and assign caller as Owner within an atomic transaction.
     */
    public function createOrganization(
        UserModel $user,
        string $name,
        string $type = 'business',
        string $defaultLocale = 'en',
        string $timezone = 'UTC'
    ): OrganizationModel {
        $cleanName = trim($name);
        if (empty($cleanName)) {
            throw new InvalidArgumentException('Organization name cannot be empty.');
        }

        $baseSlug = Str::slug($cleanName);
        $slug = $baseSlug . '-' . Str::lower(Str::random(6));

        return DB::transaction(function () use ($user, $cleanName, $slug, $type, $defaultLocale, $timezone) {
            $organization = OrganizationModel::create([
                'name' => $cleanName,
                'slug' => $slug,
                'type' => $type,
                'status' => 'active',
                'default_locale' => $defaultLocale,
                'timezone' => $timezone,
            ]);

            $ownerRole = RoleModel::where('slug', 'owner')->firstOrFail();

            OrganizationMembershipModel::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role_id' => $ownerRole->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // Set as user's current organization
            $user->update(['current_organization_id' => $organization->id]);

            $this->auditService->log(
                action: 'organization.created',
                organizationId: $organization->id,
                userId: $user->id,
                entityType: 'organization',
                entityId: (string) $organization->id,
                metadata: ['name' => $cleanName, 'slug' => $slug]
            );

            return $organization;
        });
    }

    /**
     * Fetch all organizations the user is an active member of.
     */
    public function getUserOrganizations(UserModel $user): array
    {
        $memberships = OrganizationMembershipModel::with(['organization', 'role'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('organization', fn ($q) => $q->where('status', 'active'))
            ->get();

        return $memberships->map(function (OrganizationMembershipModel $m) use ($user) {
            return [
                'id' => $m->organization->id,
                'name' => $m->organization->name,
                'slug' => $m->organization->slug,
                'type' => $m->organization->type,
                'status' => $m->organization->status,
                'role' => $m->role->slug,
                'is_current' => $user->current_organization_id === $m->organization->id,
                'joined_at' => $m->joined_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Switch active organization for caller after server-side status and membership verification.
     */
    public function switchOrganization(UserModel $user, int $targetOrgId): OrganizationModel
    {
        if ($user->status !== 'active') {
            throw new AuthorizationException('You are not authorized to access this resource.');
        }

        $membership = OrganizationMembershipModel::with(['organization', 'role'])
            ->where('user_id', $user->id)
            ->where('organization_id', $targetOrgId)
            ->where('status', 'active')
            ->whereHas('organization', fn ($q) => $q->where('status', 'active'))
            ->first();

        if (!$membership) {
            $this->auditService->log(
                action: 'authorization.denied',
                organizationId: $targetOrgId,
                userId: $user->id,
                metadata: ['reason' => 'Invalid or inactive organization membership']
            );

            throw new AuthorizationException('You are not authorized to access this resource.');
        }

        $user->update(['current_organization_id' => $targetOrgId]);
        $this->authService->flushCache();

        $this->auditService->log(
            action: 'organization.switched',
            organizationId: $targetOrgId,
            userId: $user->id
        );

        return $membership->organization;
    }

    /**
     * Update organization profile and settings under verified tenant permission and typed DTO.
     */
    public function updateOrganization(
        TenantContext $context,
        int $organizationId,
        UpdateOrganizationData $data
    ): OrganizationModel {
        TenantIsolationGuard::assertTenantAccess($organizationId, $context);
        TenantIsolationGuard::assertPermission($context, 'organization.manage');

        $org = OrganizationModel::findOrFail($organizationId);
        $updates = $data->toArray();

        if (!empty($updates)) {
            $org->update($updates);
        }

        $this->auditService->log(
            action: 'organization.updated',
            organizationId: $org->id,
            entityType: 'organization',
            entityId: (string) $org->id,
            metadata: $updates
        );

        return $org;
    }
}
