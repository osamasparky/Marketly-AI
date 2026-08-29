<?php

namespace App\Domains\Brand\Controllers;

use App\Domains\Brand\Application\Services\BrandApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(
        private readonly BrandApplicationService $brandService
    ) {}

    private function getContext(Request $request): TenantContext
    {
        return $request->attributes->get('tenant_context') ?? app(TenantContext::class);
    }

    /**
     * Get full Brand Brain with profile and completeness score.
     */
    public function show(Request $request): JsonResponse
    {
        $result = $this->brandService->getBrandBrain($this->getContext($request));

        return ApiResponse::success(
            data: $result,
            meta: ['message' => 'Brand Brain retrieved successfully.']
        );
    }

    /**
     * Create or update Brand Profile.
     */
    public function saveProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => 'required|string|min:2|max:100',
            'legal_name' => 'nullable|string|max:150',
            'industry' => 'nullable|string|max:50',
            'business_type' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:2000',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'country' => 'nullable|string|max:10',
            'region' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
            'default_locale' => 'nullable|string|in:en,ar',
            'tagline' => 'nullable|string|max:255',
            'mission' => 'nullable|string|max:1000',
            'vision' => 'nullable|string|max:1000',
            'values' => 'nullable|array',
            'positioning' => 'nullable|string|max:1000',
            'unique_selling_points' => 'nullable|array',
            'brand_promise' => 'nullable|string|max:1000',
        ]);

        $profile = $this->brandService->saveBrandProfile($this->getContext($request), $validated);

        return ApiResponse::success(
            data: ['profile' => $profile],
            meta: ['message' => 'Brand profile updated successfully.']
        );
    }

    /**
     * Products & Services CRUD
     */
    public function listProducts(Request $request): JsonResponse
    {
        $brain = $this->brandService->getBrandBrain($this->getContext($request));
        return ApiResponse::success(['products' => $brain['profile']?->productsServices ?? []]);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'type' => 'required|string|in:product,service',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'url' => 'nullable|url|max:255',
            'features' => 'nullable|array',
        ]);

        $product = $this->brandService->saveProductService($this->getContext($request), $validated);
        return ApiResponse::success(['product' => $product], ['message' => 'Product created successfully.'], 201);
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'type' => 'sometimes|string|in:product,service',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'url' => 'nullable|url|max:255',
            'features' => 'nullable|array',
        ]);

        $product = $this->brandService->saveProductService($this->getContext($request), $validated, $id);
        return ApiResponse::success(['product' => $product], ['message' => 'Product updated successfully.']);
    }

    public function deleteProduct(Request $request, int $id): JsonResponse
    {
        $this->brandService->deleteProductService($this->getContext($request), $id);
        return ApiResponse::success(null, ['message' => 'Product deleted successfully.']);
    }

    /**
     * Target Audience CRUD
     */
    public function listAudiences(Request $request): JsonResponse
    {
        $brain = $this->brandService->getBrandBrain($this->getContext($request));
        return ApiResponse::success(['audiences' => $brain['profile']?->audiences ?? []]);
    }

    public function storeAudience(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'type' => 'required|string|in:b2c,b2b',
            'description' => 'nullable|string|max:1000',
            'age_range' => 'nullable|string|max:30',
            'gender' => 'nullable|string|max:20',
            'locations' => 'nullable|array',
            'interests' => 'nullable|array',
            'pain_points' => 'nullable|array',
            'needs' => 'nullable|array',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'job_titles' => 'nullable|array',
        ]);

        $audience = $this->brandService->saveAudience($this->getContext($request), $validated);
        return ApiResponse::success(['audience' => $audience], ['message' => 'Audience profile created.'], 201);
    }

    public function updateAudience(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'type' => 'sometimes|string|in:b2c,b2b',
            'description' => 'nullable|string|max:1000',
            'age_range' => 'nullable|string|max:30',
            'gender' => 'nullable|string|max:20',
            'locations' => 'nullable|array',
            'interests' => 'nullable|array',
            'pain_points' => 'nullable|array',
            'needs' => 'nullable|array',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'job_titles' => 'nullable|array',
        ]);

        $audience = $this->brandService->saveAudience($this->getContext($request), $validated, $id);
        return ApiResponse::success(['audience' => $audience], ['message' => 'Audience profile updated.']);
    }

    public function deleteAudience(Request $request, int $id): JsonResponse
    {
        $this->brandService->deleteAudience($this->getContext($request), $id);
        return ApiResponse::success(null, ['message' => 'Audience profile deleted.']);
    }

    /**
     * Brand Voice & Tone
     */
    public function getVoice(Request $request): JsonResponse
    {
        $brain = $this->brandService->getBrandBrain($this->getContext($request));
        return ApiResponse::success(['voice' => $brain['profile']?->voice]);
    }

    public function saveVoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_tones' => 'nullable|array',
            'formality_scale' => 'nullable|integer|min:1|max:5',
            'playfulness_scale' => 'nullable|integer|min:1|max:5',
            'boldness_scale' => 'nullable|integer|min:1|max:5',
            'simplicity_scale' => 'nullable|integer|min:1|max:5',
            'preferred_phrases' => 'nullable|array',
            'forbidden_phrases' => 'nullable|array',
            'words_to_avoid' => 'nullable|array',
            'words_to_emphasize' => 'nullable|array',
            'cta_preferences' => 'nullable|array',
            'emoji_style' => 'nullable|string|in:none,minimal,moderate,expressive',
            'hashtag_style' => 'nullable|string|max:30',
            'dialect' => 'nullable|string|max:50',
        ]);

        $voice = $this->brandService->saveBrandVoice($this->getContext($request), $validated);
        return ApiResponse::success(['voice' => $voice], ['message' => 'Brand voice updated successfully.']);
    }

    /**
     * Goals CRUD
     */
    public function listGoals(Request $request): JsonResponse
    {
        $brain = $this->brandService->getBrandBrain($this->getContext($request));
        return ApiResponse::success(['goals' => $brain['profile']?->goals ?? []]);
    }

    public function storeGoal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'goal_type' => 'required|string|max:50',
            'priority' => 'required|string|in:primary,secondary,tertiary',
            'description' => 'nullable|string|max:1000',
            'target_metrics' => 'nullable|array',
        ]);

        $goal = $this->brandService->saveGoal($this->getContext($request), $validated);
        return ApiResponse::success(['goal' => $goal], ['message' => 'Goal created.'], 201);
    }

    public function updateGoal(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'goal_type' => 'sometimes|string|max:50',
            'priority' => 'sometimes|string|in:primary,secondary,tertiary',
            'description' => 'nullable|string|max:1000',
            'target_metrics' => 'nullable|array',
        ]);

        $goal = $this->brandService->saveGoal($this->getContext($request), $validated, $id);
        return ApiResponse::success(['goal' => $goal], ['message' => 'Goal updated.']);
    }

    public function deleteGoal(Request $request, int $id): JsonResponse
    {
        $this->brandService->deleteGoal($this->getContext($request), $id);
        return ApiResponse::success(null, ['message' => 'Goal deleted.']);
    }

    /**
     * Competitors CRUD
     */
    public function listCompetitors(Request $request): JsonResponse
    {
        $brain = $this->brandService->getBrandBrain($this->getContext($request));
        return ApiResponse::success(['competitors' => $brain['profile']?->competitors ?? []]);
    }

    public function storeCompetitor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'positioning' => 'nullable|string|max:500',
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        $competitor = $this->brandService->saveCompetitor($this->getContext($request), $validated);
        return ApiResponse::success(['competitor' => $competitor], ['message' => 'Competitor added.'], 201);
    }

    public function updateCompetitor(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'positioning' => 'nullable|string|max:500',
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        $competitor = $this->brandService->saveCompetitor($this->getContext($request), $validated, $id);
        return ApiResponse::success(['competitor' => $competitor], ['message' => 'Competitor updated.']);
    }

    public function deleteCompetitor(Request $request, int $id): JsonResponse
    {
        $this->brandService->deleteCompetitor($this->getContext($request), $id);
        return ApiResponse::success(null, ['message' => 'Competitor removed.']);
    }

    /**
     * Preview Sanitized AI Brand Context for the current tenant.
     */
    public function aiContext(Request $request): JsonResponse
    {
        $task = $request->query('task', 'content_generation');
        $audienceId = $request->query('audience_id') ? (int) $request->query('audience_id') : null;
        $productId = $request->query('product_id') ? (int) $request->query('product_id') : null;
        $platform = $request->query('platform');

        $context = $this->brandService->getAIBrandContext($this->getContext($request));

        $minimized = match ($task) {
            'content_generation' => $context->forContentGeneration($audienceId, $productId, $platform),
            default => $context->toArray(),
        };

        return ApiResponse::success([
            'context' => $minimized,
            'system_block' => $context->toSanitizedSystemBlock(),
        ]);
    }
}
