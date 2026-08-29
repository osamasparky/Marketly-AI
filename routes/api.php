<?php

use App\Domains\Identity\Controllers\AuthController;
use App\Domains\Tenancy\Controllers\MembershipController;
use App\Domains\Tenancy\Controllers\OrganizationController;
use App\Http\Controllers\Api\V1\CspReportController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Middleware\TenantContextMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketly-AI API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Health Check Endpoint
    Route::get('/health', [HealthController::class, 'index'])->name('api.v1.health');

    // CSP Violation Reporting (Rate Limited)
    Route::post('/csp-report', CspReportController::class)->middleware('throttle:30,1')->name('api.v1.csp.report');

    // Authentication Endpoints (Strict Rate Limiting)
    Route::prefix('auth')->middleware('throttle:15,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.v1.auth.forgot_password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.v1.auth.reset_password');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        });
    });

    // Authenticated & Tenant Context Routes
    Route::middleware(['auth:sanctum', TenantContextMiddleware::class])->group(function () {
        // User Profile & Capabilities
        Route::get('/me', [AuthController::class, 'me'])->name('api.v1.me');

        // Organizations (Tenants)
        Route::get('/organizations', [OrganizationController::class, 'index'])->name('api.v1.organizations.index');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('api.v1.organizations.store');
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('api.v1.organizations.show');
        Route::patch('/organizations/{organization}', [OrganizationController::class, 'update'])->name('api.v1.organizations.update');
        Route::post('/organizations/{organization}/switch', [OrganizationController::class, 'switch'])->name('api.v1.organizations.switch');

        // Organization Members & Invitations
        Route::get('/organizations/{organization}/members', [MembershipController::class, 'index'])->name('api.v1.members.index');
        Route::post('/organizations/{organization}/invitations', [MembershipController::class, 'invite'])->name('api.v1.invitations.invite');
        Route::patch('/organizations/{organization}/members/{user}', [MembershipController::class, 'updateRole'])->name('api.v1.members.update_role');
        Route::delete('/organizations/{organization}/members/{user}', [MembershipController::class, 'destroy'])->name('api.v1.members.destroy');

        // Accept Invitation
        Route::post('/invitations/accept', [MembershipController::class, 'accept'])->name('api.v1.invitations.accept');
    });
});
