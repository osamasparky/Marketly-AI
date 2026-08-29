<?php

namespace App\Domains\Tenancy\Controllers;

use App\Domains\Tenancy\Application\Services\OrganizationApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationApplicationService $orgService
    ) {}

    /**
     * List all organizations where the authenticated user is an active member.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizations = $this->orgService->getUserOrganizations($user);

        return ApiResponse::success(
            data: ['organizations' => $organizations],
            meta: ['message' => 'Organizations retrieved successfully.']
        );
    }

    /**
     * Create a new organization tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'type' => 'sometimes|string|in:business,agency',
            'default_locale' => 'sometimes|string|in:en,ar',
            'timezone' => 'sometimes|string|max:50',
        ]);

        $organization = $this->orgService->createOrganization(
            user: $request->user(),
            name: $validated['name'],
            type: $validated['type'] ?? 'business',
            defaultLocale: $validated['default_locale'] ?? 'en',
            timezone: $validated['timezone'] ?? 'UTC'
        );

        return ApiResponse::success(
            data: ['organization' => $organization],
            meta: ['message' => 'Organization created successfully.'],
            status: 201
        );
    }

    /**
     * View organization details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $context = $request->attributes->get('tenant_context') ?? (app()->bound(TenantContext::class) ? app(TenantContext::class) : null);
        if ($context) {
            TenantIsolationGuard::assertTenantAccess($id, $context);
            TenantIsolationGuard::assertPermission($context, 'organization.view');
        }

        $org = OrganizationModel::findOrFail($id);

        return ApiResponse::success(
            data: ['organization' => $org],
            meta: ['message' => 'Organization details retrieved.']
        );
    }

    /**
     * Update organization settings.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $context = $request->attributes->get('tenant_context') ?? app(TenantContext::class);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'type' => 'sometimes|string|in:business,agency',
            'default_locale' => 'sometimes|string|in:en,ar',
            'timezone' => 'sometimes|string|max:50',
        ]);

        $org = $this->orgService->updateOrganization($context, $id, $validated);

        return ApiResponse::success(
            data: ['organization' => $org],
            meta: ['message' => 'Organization updated successfully.']
        );
    }

    /**
     * Switch active organization tenant.
     */
    public function switch(Request $request, int $id): JsonResponse
    {
        $org = $this->orgService->switchOrganization($request->user(), $id);

        return ApiResponse::success(
            data: ['organization' => $org],
            meta: ['message' => 'Switched active organization successfully.']
        );
    }
}
