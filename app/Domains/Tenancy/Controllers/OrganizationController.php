<?php

namespace App\Domains\Tenancy\Controllers;

use App\Domains\Tenancy\Application\DTOs\UpdateOrganizationData;
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
     * Update organization settings with typed DTO.
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

        $dto = new UpdateOrganizationData(
            name: $validated['name'] ?? null,
            type: $validated['type'] ?? null,
            defaultLocale: $validated['default_locale'] ?? null,
            timezone: $validated['timezone'] ?? null
        );

        $org = $this->orgService->updateOrganization($context, $id, $dto);

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

    /**
     * Get company AI model preferences and masked API keys.
     */
    public function getAiConfig(Request $request, int $id): JsonResponse
    {
        $context = $request->attributes->get('tenant_context') ?? app(TenantContext::class);
        $result = $this->orgService->getAiConfig($context, $id);

        return ApiResponse::success(
            data: $result,
            meta: ['message' => 'AI configuration retrieved.']
        );
    }

    /**
     * Update encrypted AI API keys and company preferences.
     */
    public function updateAiConfig(Request $request, int $id): JsonResponse
    {
        $context = $request->attributes->get('tenant_context') ?? app(TenantContext::class);

        $validated = $request->validate([
            'preferred_model' => 'nullable|string|in:gemini-1.5-pro,gemini-1.5-flash,gpt-4o,gpt-4o-mini,claude-3-5-sonnet,deepseek-chat',
            'gemini_api_key' => 'nullable|string|max:500',
            'openai_api_key' => 'nullable|string|max:500',
            'anthropic_api_key' => 'nullable|string|max:500',
            'deepseek_api_key' => 'nullable|string|max:500',
            'custom_instructions' => 'nullable|string|max:2000',
            'website_url' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:100',
            'billing_email' => 'nullable|email|max:255',
        ]);

        $org = $this->orgService->updateAiConfig($context, $id, $validated);

        return ApiResponse::success(
            data: ['organization' => $org],
            meta: ['message' => 'AI settings & company profile saved successfully.']
        );
    }
}
