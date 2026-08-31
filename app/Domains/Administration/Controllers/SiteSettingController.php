<?php

namespace App\Domains\Administration\Controllers;

use App\Domains\Administration\Infrastructure\Persistence\Models\SiteSettingModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    public function __construct(
        private readonly AuditApplicationService $auditService
    ) {}

    /**
     * Get public site settings (accessible to landing page).
     */
    public function getPublicSettings(): JsonResponse
    {
        $settings = SiteSettingModel::getAllFormatted();

        return ApiResponse::success(
            data: ['settings' => $settings],
            meta: ['message' => 'Site settings retrieved successfully.']
        );
    }

    /**
     * Update site settings (Super Admin protected).
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        foreach ($payload['settings'] as $key => $val) {
            $type = 'string';
            $serialized = $val;

            if (is_array($val)) {
                $type = 'json';
                $serialized = json_encode($val);
            } elseif (is_bool($val)) {
                $type = 'boolean';
                $serialized = $val ? '1' : '0';
            } elseif (is_numeric($val)) {
                $type = 'number';
                $serialized = (string) $val;
            }

            SiteSettingModel::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $serialized,
                    'type' => $type,
                ]
            );
        }

        $this->auditService->log(
            action: 'super_admin.site_settings_updated',
            organizationId: null,
            userId: (int) auth()->id(),
            entityType: 'site_settings',
            entityId: 'global',
            metadata: ['updated_keys' => array_keys($payload['settings'])]
        );

        $freshSettings = SiteSettingModel::getAllFormatted();

        return ApiResponse::success(
            data: ['settings' => $freshSettings],
            meta: ['message' => 'Site settings updated successfully.']
        );
    }
}
