<?php

use App\Domains\Brand\Controllers\BrandController;
use App\Domains\Content\Controllers\ContentController;
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

    // Public Billing Plans
    Route::get('/billing/plans', [\App\Domains\Billing\Controllers\BillingController::class, 'listPlans'])->name('api.v1.billing.plans.public');

    // Public Site Settings
    Route::get('/site-settings', [\App\Domains\Administration\Controllers\SiteSettingController::class, 'getPublicSettings'])->name('api.v1.site_settings.public');

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
        Route::get('/organizations/{organization}/ai-config', [OrganizationController::class, 'getAiConfig'])->name('api.v1.organizations.ai_config.show');
        Route::patch('/organizations/{organization}/ai-config', [OrganizationController::class, 'updateAiConfig'])->name('api.v1.organizations.ai_config.update');

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
            Route::get('/brands', [BrandController::class, 'index'])->name('api.v1.brand.index');
            Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('api.v1.brand.destroy');
            Route::post('/', [BrandController::class, 'saveProfile'])->name('api.v1.brand.save');
            Route::patch('/', [BrandController::class, 'saveProfile'])->name('api.v1.brand.update');
            Route::get('/ai-context', [BrandController::class, 'aiContext'])->name('api.v1.brand.ai_context');

            // Products & Services
            Route::get('/products', [BrandController::class, 'listProducts'])->name('api.v1.brand.products.index');
            Route::post('/products', [BrandController::class, 'storeProduct'])->name('api.v1.brand.products.store');
            Route::patch('/products/{product}', [BrandController::class, 'updateProduct'])->name('api.v1.brand.products.update');
            Route::delete('/products/{product}', [BrandController::class, 'deleteProduct'])->name('api.v1.brand.products.destroy');
            Route::post('/products/{product}/images', [BrandController::class, 'uploadProductImages'])->name('api.v1.brand.products.images.store');
            Route::delete('/products/{product}/images/{asset}', [BrandController::class, 'deleteProductImage'])->name('api.v1.brand.products.images.destroy');

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

            // Brand Assets & Visual Identity (Logos, Guidelines, etc.)
            Route::get('/assets', [BrandController::class, 'listAssets'])->name('api.v1.brand.assets.index');
            Route::post('/assets', [BrandController::class, 'uploadAsset'])->name('api.v1.brand.assets.store');
            Route::delete('/assets/{asset}', [BrandController::class, 'deleteAsset'])->name('api.v1.brand.assets.destroy');
        });

        // Phase 3: AI Marketing Strategy API
        Route::prefix('strategy')->group(function () {
            Route::get('/', [StrategyController::class, 'index'])->name('api.v1.strategy.index');
            Route::post('/generate', [StrategyController::class, 'generate'])->middleware('throttle:10,1')->name('api.v1.strategy.generate');
            Route::get('/{strategy}', [StrategyController::class, 'show'])->name('api.v1.strategy.show');
            Route::patch('/{strategy}', [StrategyController::class, 'update'])->name('api.v1.strategy.update');
            Route::delete('/{strategy}', [StrategyController::class, 'destroy'])->name('api.v1.strategy.destroy');
            Route::post('/{strategy}/activate', [StrategyController::class, 'activate'])->name('api.v1.strategy.activate');
            Route::post('/{strategy}/pause', [StrategyController::class, 'pause'])->name('api.v1.strategy.pause');
            Route::post('/{strategy}/archive', [StrategyController::class, 'archive'])->name('api.v1.strategy.archive');
            Route::get('/{strategy}/health', [StrategyController::class, 'health'])->name('api.v1.strategy.health');

            // Content Pillars
            Route::get('/{strategy}/pillars', [StrategyController::class, 'listPillars'])->name('api.v1.strategy.pillars.index');
            Route::post('/{strategy}/pillars', [StrategyController::class, 'storePillar'])->name('api.v1.strategy.pillars.store');
            Route::patch('/{strategy}/pillars/{pillar}', [StrategyController::class, 'updatePillar'])->name('api.v1.strategy.pillars.update');
            Route::delete('/{strategy}/pillars/{pillar}', [StrategyController::class, 'deletePillar'])->name('api.v1.strategy.pillars.destroy');

            // Campaign Themes
            Route::get('/{strategy}/campaign-themes', [StrategyController::class, 'listCampaignThemes'])->name('api.v1.strategy.campaign_themes.index');
            Route::post('/{strategy}/campaign-themes', [StrategyController::class, 'storeCampaignTheme'])->name('api.v1.strategy.campaign_themes.store');

            // Opportunities
            Route::get('/{strategy}/opportunities', [StrategyController::class, 'listOpportunities'])->name('api.v1.strategy.opportunities.index');
            Route::post('/{strategy}/opportunities', [StrategyController::class, 'storeOpportunity'])->name('api.v1.strategy.opportunities.store');
        });

        // Phase 3.5: Billing & Subscriptions API
        Route::prefix('billing')->group(function () {
            Route::get('/subscription', [\App\Domains\Billing\Controllers\BillingController::class, 'getSubscription'])->name('api.v1.billing.subscription');
            Route::post('/subscription/select-plan', [\App\Domains\Billing\Controllers\BillingController::class, 'selectPlan'])->name('api.v1.billing.subscription.select_plan');
            Route::post('/subscription/cancel', [\App\Domains\Billing\Controllers\BillingController::class, 'cancel'])->name('api.v1.billing.subscription.cancel');
        });

        // Phase 4: Content Studio API
        Route::prefix('content')->group(function () {
            Route::get('/', [ContentController::class, 'index'])->name('api.v1.content.index');
            Route::post('/generate', [ContentController::class, 'generate'])->middleware('throttle:20,1')->name('api.v1.content.generate');
            Route::get('/{content}', [ContentController::class, 'show'])->name('api.v1.content.show');
            Route::patch('/{content}', [ContentController::class, 'update'])->name('api.v1.content.update');
            Route::delete('/{content}', [ContentController::class, 'destroy'])->name('api.v1.content.destroy');
            Route::patch('/{content}/variations/{platform}', [ContentController::class, 'updateVariation'])->name('api.v1.content.variations.update');
            Route::post('/{content}/regenerate', [ContentController::class, 'regenerate'])->name('api.v1.content.regenerate');
            Route::post('/{content}/repurpose', [ContentController::class, 'repurpose'])->name('api.v1.content.repurpose');
            Route::post('/{content}/quality-check', [ContentController::class, 'qualityCheck'])->name('api.v1.content.quality_check');
            Route::post('/{content}/approve', [ContentController::class, 'approve'])->name('api.v1.content.approve');
            Route::post('/{content}/schedule', [ContentController::class, 'schedule'])->name('api.v1.content.schedule');
        });

        // Phase 5: Creative Studio API
        Route::prefix('creative')->group(function () {
            Route::get('/assets', [\App\Domains\Creative\Controllers\CreativeController::class, 'index'])->name('api.v1.creative.assets.index');
            Route::post('/generate', [\App\Domains\Creative\Controllers\CreativeController::class, 'generate'])->middleware('throttle:20,1')->name('api.v1.creative.generate');
            Route::post('/generate-reel', [\App\Domains\Creative\Controllers\CreativeController::class, 'generateReel'])->middleware('throttle:20,1')->name('api.v1.creative.generate_reel');
            Route::get('/assets/{asset}', [\App\Domains\Creative\Controllers\CreativeController::class, 'show'])->name('api.v1.creative.assets.show');
            Route::post('/assets/{asset}/attach', [\App\Domains\Creative\Controllers\CreativeController::class, 'attach'])->name('api.v1.creative.assets.attach');
            Route::delete('/assets/{asset}', [\App\Domains\Creative\Controllers\CreativeController::class, 'destroy'])->name('api.v1.creative.assets.destroy');
        });

        // Phase 6: Marketing Calendar & Approvals API
        Route::prefix('calendar')->group(function () {
            Route::get('/', [\App\Domains\Calendar\Controllers\CalendarController::class, 'index'])->name('api.v1.calendar.index');
            Route::post('/plan', [\App\Domains\Calendar\Controllers\CalendarController::class, 'plan'])->middleware('throttle:10,1')->name('api.v1.calendar.plan');
            Route::post('/posts/{post}/reschedule', [\App\Domains\Calendar\Controllers\CalendarController::class, 'reschedule'])->name('api.v1.calendar.posts.reschedule');
            Route::post('/posts/{post}/submit-review', [\App\Domains\Calendar\Controllers\CalendarController::class, 'submitReview'])->name('api.v1.calendar.posts.submit_review');
            Route::post('/posts/{post}/approve', [\App\Domains\Calendar\Controllers\CalendarController::class, 'approve'])->name('api.v1.calendar.posts.approve');
            Route::post('/posts/{post}/schedule', [\App\Domains\Calendar\Controllers\CalendarController::class, 'schedule'])->name('api.v1.calendar.posts.schedule');
            Route::post('/posts/{post}/unschedule', [\App\Domains\Calendar\Controllers\CalendarController::class, 'unschedule'])->name('api.v1.calendar.posts.unschedule');
        });

        // Phase 7: Social Publishing & Multi-Platform OAuth API
        Route::prefix('social')->group(function () {
            Route::get('/accounts', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'index'])->name('api.v1.social.accounts.index');
            Route::get('/oauth/{platform}/redirect', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'getOAuthUrl'])->name('api.v1.social.oauth.redirect');
            Route::post('/oauth/{platform}/callback', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'handleCallback'])->name('api.v1.social.oauth.callback');
            Route::post('/accounts/{platform}/connect-custom', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'connectCustom'])->name('api.v1.social.accounts.connect_custom');
            Route::get('/pages/{platform}', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'getPages'])->name('api.v1.social.pages.index');
            Route::post('/accounts/{account}/health-check', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'healthCheck'])->name('api.v1.social.accounts.health_check');
            Route::delete('/accounts/{account}', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'disconnect'])->name('api.v1.social.accounts.disconnect');
            Route::get('/ready-posts', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'getReadyPosts'])->name('api.v1.social.posts.ready');
            Route::post('/posts/{post}/publish-now', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'publishNow'])->name('api.v1.social.posts.publish_now');
            Route::get('/jobs', [\App\Domains\Publishing\Controllers\SocialPublishingController::class, 'getJobs'])->name('api.v1.social.jobs.index');
        });

        // Phase 8: Analytics, Performance & AI Learning API
        Route::prefix('analytics')->group(function () {
            Route::get('/overview', [\App\Domains\Analytics\Controllers\AnalyticsController::class, 'overview'])->name('api.v1.analytics.overview');
            Route::get('/content', [\App\Domains\Analytics\Controllers\AnalyticsController::class, 'content'])->name('api.v1.analytics.content');
            Route::get('/pillars', [\App\Domains\Analytics\Controllers\AnalyticsController::class, 'pillars'])->name('api.v1.analytics.pillars');
            Route::post('/sync', [\App\Domains\Analytics\Controllers\AnalyticsController::class, 'sync'])->name('api.v1.analytics.sync');
            Route::get('/recommendations', [\App\Domains\Analytics\Controllers\AnalyticsController::class, 'recommendations'])->name('api.v1.analytics.recommendations');
            Route::post('/recommendations/{recommendation}/apply', [\App\Domains\Analytics\Controllers\AnalyticsController::class, 'applyRecommendation'])->name('api.v1.analytics.recommendations.apply');
            Route::post('/recommendations/{recommendation}/dismiss', [\App\Domains\Analytics\Controllers\AnalyticsController::class, 'dismissRecommendation'])->name('api.v1.analytics.recommendations.dismiss');
        });

        // Super Admin Platform Management
        Route::prefix('super-admin')->middleware(\App\Http\Middleware\EnsureSuperAdmin::class)->group(function () {
            Route::get('/kpis', [\App\Domains\Administration\Controllers\SuperAdminController::class, 'kpis'])->name('api.v1.super_admin.kpis');
            Route::get('/organizations', [\App\Domains\Administration\Controllers\SuperAdminController::class, 'organizations'])->name('api.v1.super_admin.organizations.index');
            Route::patch('/organizations/{id}/status', [\App\Domains\Administration\Controllers\SuperAdminController::class, 'updateStatus'])->name('api.v1.super_admin.organizations.status');
            Route::patch('/organizations/{id}/plan', [\App\Domains\Administration\Controllers\SuperAdminController::class, 'updatePlan'])->name('api.v1.super_admin.organizations.plan');
            Route::post('/organizations/{id}/impersonate', [\App\Domains\Administration\Controllers\SuperAdminController::class, 'impersonate'])->name('api.v1.super_admin.organizations.impersonate');
            Route::get('/subscriptions', [\App\Domains\Administration\Controllers\SuperAdminController::class, 'subscriptions'])->name('api.v1.super_admin.subscriptions.index');
            Route::get('/reports', [\App\Domains\Administration\Controllers\SuperAdminController::class, 'reports'])->name('api.v1.super_admin.reports');

            // Phase B: Super Admin Plans Management CRUD
            Route::get('/plans', [\App\Domains\Administration\Controllers\SuperAdminPlanController::class, 'index'])->name('api.v1.super_admin.plans.index');
            Route::post('/plans', [\App\Domains\Administration\Controllers\SuperAdminPlanController::class, 'store'])->name('api.v1.super_admin.plans.store');
            Route::patch('/plans/{id}', [\App\Domains\Administration\Controllers\SuperAdminPlanController::class, 'update'])->name('api.v1.super_admin.plans.update');
            Route::delete('/plans/{id}', [\App\Domains\Administration\Controllers\SuperAdminPlanController::class, 'destroy'])->name('api.v1.super_admin.plans.destroy');

            // Phase C: Super Admin Site Settings Management
            Route::patch('/site-settings', [\App\Domains\Administration\Controllers\SiteSettingController::class, 'updateSettings'])->name('api.v1.super_admin.site_settings.update');
        });
    });
});

