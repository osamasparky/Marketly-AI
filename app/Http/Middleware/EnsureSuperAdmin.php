<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_super_admin) {
            return ApiResponse::error(
                message: 'Access denied. Super Administrator privileges required.',
                code: 'SUPER_ADMIN_REQUIRED',
                status: Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
