<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Get system health and runtime status.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $dbStatus = 'connected';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        return ApiResponse::success([
            'status' => 'healthy',
            'app_name' => config('app.name', 'Marketly-AI'),
            'environment' => config('app.env'),
            'api_version' => 'v1',
            'php_version' => PHP_VERSION,
            'database' => $dbStatus,
            'system_time' => now()->toIso8601String(),
            'modules' => [
                'identity' => 'active',
                'tenancy' => 'ready',
                'brand_brain' => 'ready',
                'ai_strategy' => 'ready',
                'content_studio' => 'ready',
                'creative_studio' => 'ready',
                'calendar' => 'ready',
                'publishing' => 'ready',
                'analytics' => 'ready',
            ],
        ]);
    }
}
