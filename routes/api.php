<?php

use App\Domains\Brand\Controllers\BrandController;
use App\Domains\Identity\Controllers\AuthController;
use App\Domains\Strategy\Controllers\StrategyController;
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

        // Phase 2: Brand Brain API
        Route::prefix('brand')->group(function () {
            Route::get('/', [BrandController::class, 'show'])->name('api.v1.brand.show');
            Route::post('/', [BrandController::class, 'saveProfile'])->name('api.v1.brand.save');
            Route::patch('/', [BrandController::class, 'saveProfile'])->name('api.v1.brand.update');
            Route::get('/ai-context', [BrandController::class, 'aiContext'])->name('api.v1.brand.ai_context');

            // Products & Services
            Route::get('/products', [BrandController::class, 'listProducts'])->name('api.v1.brand.products.index');
            Route::post('/products', [BrandController::class, 'storeProduct'])->name('api.v1.brand.products.store');
            Route::patch('/products/{product}', [BrandController::class, 'updateProduct'])->name('api.v1.brand.products.update');
            Route::delete('/products/{product}', [BrandController::class, 'deleteProduct'])->name('api.v1.brand.products.destroy');

            // Target Audiences
            Route::get('/audiences', [BrandController::class, 'listAudiences'])->name('api.v1.brand.audiences.index');
            Route::post('/audiences', [BrandController::class, 'storeAudience'])->name('api.v1.brand.audiences.store');
            Route::patch('/audiences/{audience}', [BrandController::class, 'updateAudience'])->name('api.v1.brand.audiences.update');
            Route::delete('/audiences/{audience}', [BrandController::class, 'deleteAudience'])->name('api.v1.brand.audiences.destroy');

            // Brand Voice & Tone
            Route::get('/voice', [BrandController::class, 'getVoice'])->name('api.v1.brand.voice.show');
            Route::patch('/voice', [BrandController::class, 'saveVoice'])->name('api.v1.brand.voice.save');

            // Goals
            Route::get('/goals', [BrandController::class, 'listGoals'])->name('api.v1.brand.goals.index');
            Route::post('/goals', [BrandController::class, 'storeGoal'])->name('api.v1.brand.goals.store');
            Route::patch('/goals/{goal}', [BrandController::class, 'updateGoal'])->name('api.v1.brand.goals.update');
            Route::delete('/goals/{goal}', [BrandController::class, 'deleteGoal'])->name('api.v1.brand.goals.destroy');

            // Competitors
            Route::get('/competitors', [BrandController::class, 'listCompetitors'])->name('api.v1.brand.competitors.index');
            Route::post('/competitors', [BrandController::class, 'storeCompetitor'])->name('api.v1.brand.competitors.store');
            Route::patch('/competitors/{competitor}', [BrandController::class, 'updateCompetitor'])->name('api.v1.brand.competitors.update');
            Route::delete('/competitors/{competitor}', [BrandController::class, 'deleteCompetitor'])->name('api.v1.brand.competitors.destroy');
        });

        // Phase 3: AI Marketing Strategy API
        Route::prefix('strategy')->group(function () {
            Route::get('/', [StrategyController::class, 'index'])->name('api.v1.strategy.index');
            Route::post('/generate', [StrategyController::class, 'generate'])->middleware('throttle:10,1')->name('api.v1.strategy.generate');
            Route::patch('/{strategy}', [StrategyController::class, 'update'])->name('api.v1.strategy.update');
            Route::post('/{strategy}/activate', [StrategyController::class, 'activate'])->name('api.v1.strategy.activate');
            Route::post('/{strategy}/pause', [StrategyController::class, 'pause'])->name('api.v1.strategy.pause');

            // Content Pillars
            Route::post('/{strategy}/pillars', [StrategyController::class, 'storePillar'])->name('api.v1.strategy.pillars.store');
            Route::patch('/{strategy}/pillars/{pillar}', [StrategyController::class, 'updatePillar'])->name('api.v1.strategy.pillars.update');
            Route::delete('/{strategy}/pillars/{pillar}', [StrategyController::class, 'deletePillar'])->name('api.v1.strategy.pillars.destroy');
        });
    });
});
