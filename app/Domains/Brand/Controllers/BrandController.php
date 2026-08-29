<?php

namespace App\Domains\Brand\Controllers;

use App\Domains\Brand\Application\DTOs\SaveAudienceData;
use App\Domains\Brand\Application\DTOs\SaveBrandProfileData;
use App\Domains\Brand\Application\DTOs\SaveBrandVoiceData;
use App\Domains\Brand\Application\DTOs\SaveCompetitorData;
use App\Domains\Brand\Application\DTOs\SaveGoalData;
use App\Domains\Brand\Application\DTOs\SaveProductServiceData;
use App\Domains\Brand\Application\Services\BrandApplicationService;
use App\Domains\Brand\Presentation\Resources\AudienceResource;
use App\Domains\Brand\Presentation\Resources\BrandProfileResource;
use App\Domains\Brand\Presentation\Resources\BrandVoiceResource;
use App\Domains\Brand\Presentation\Resources\CompetitorResource;
use App\Domains\Brand\Presentation\Resources\GoalResource;
use App\Domains\Brand\Presentation\Resources\ProductServiceResource;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    private const SAFE_URL_REGEX = '/^https?:\/\/[a-zA-Z0-9\-\.]+(\.[a-zA-Z]{2,})+(:[0-9]+)?(\/.*)?$/i';

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
            data: [
                'profile' => $result['profile'] ? new BrandProfileResource($result['profile']) : null,
                'completeness' => $result['completeness'],
            ],
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
            'website' => ['nullable', 'string', 'max:255', 'regex:' . self::SAFE_URL_REGEX],
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
            'values' => 'nullable|array|max:20',
            'values.*' => 'string|max:100',
            'positioning' => 'nullable|string|max:1000',
            'unique_selling_points' => 'nullable|array|max:20',
            'unique_selling_points.*' => 'string|max:255',
            'brand_promise' => 'nullable|string|max:1000',
        ]);

        $dto = SaveBrandProfileData::fromArray($validated);
        $profile = $this->brandService->saveBrandProfile($this->getContext($request), $dto);

        return ApiResponse::success(
            data: ['profile' => new BrandProfileResource($profile)],
            meta: ['message' => 'Brand profile updated successfully.']
        );
    }

    /**
     * Products & Services CRUD
     */
    public function listProducts(Request $request): JsonResponse
    {
        $products = $this->brandService->listProducts($this->getContext($request));
        return ApiResponse::success(['products' => ProductServiceResource::collection($products)]);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'type' => 'required|string|in:product,service',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0|max:10000000',
            'currency' => 'nullable|string|max:10',
            'url' => ['nullable', 'string', 'max:255', 'regex:' . self::SAFE_URL_REGEX],
            'features' => 'nullable|array|max:30',
            'features.*' => 'string|max:255',
        ]);

        $dto = SaveProductServiceData::fromArray($validated);
        $product = $this->brandService->saveProductService($this->getContext($request), $dto);

        return ApiResponse::success(
            data: ['product' => new ProductServiceResource($product)],
            meta: ['message' => 'Product created successfully.'],
            status: 201
        );
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'type' => 'sometimes|string|in:product,service',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0|max:10000000',
            'currency' => 'nullable|string|max:10',
            'url' => ['nullable', 'string', 'max:255', 'regex:' . self::SAFE_URL_REGEX],
            'features' => 'nullable|array|max:30',
            'features.*' => 'string|max:255',
        ]);

        $dto = SaveProductServiceData::fromArray($validated);
        $product = $this->brandService->saveProductService($this->getContext($request), $dto, $id);

        return ApiResponse::success(
            data: ['product' => new ProductServiceResource($product)],
            meta: ['message' => 'Product updated successfully.']
        );
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
        $audiences = $this->brandService->listAudiences($this->getContext($request));
        return ApiResponse::success(['audiences' => AudienceResource::collection($audiences)]);
    }

    public function storeAudience(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'type' => 'required|string|in:b2c,b2b',
            'description' => 'nullable|string|max:1000',
            'age_range' => 'nullable|string|max:30',
            'gender' => 'nullable|string|max:20',
            'locations' => 'nullable|array|max:20',
            'locations.*' => 'string|max:100',
            'interests' => 'nullable|array|max:30',
            'interests.*' => 'string|max:100',
            'pain_points' => 'nullable|array|max:30',
            'pain_points.*' => 'string|max:255',
            'needs' => 'nullable|array|max:30',
            'needs.*' => 'string|max:255',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'job_titles' => 'nullable|array|max:30',
            'job_titles.*' => 'string|max:100',
        ]);

        $dto = SaveAudienceData::fromArray($validated);
        $audience = $this->brandService->saveAudience($this->getContext($request), $dto);

        return ApiResponse::success(
            data: ['audience' => new AudienceResource($audience)],
            meta: ['message' => 'Audience profile created.'],
            status: 201
        );
    }

    public function updateAudience(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'type' => 'sometimes|string|in:b2c,b2b',
            'description' => 'nullable|string|max:1000',
            'age_range' => 'nullable|string|max:30',
            'gender' => 'nullable|string|max:20',
            'locations' => 'nullable|array|max:20',
            'locations.*' => 'string|max:100',
            'interests' => 'nullable|array|max:30',
            'interests.*' => 'string|max:100',
            'pain_points' => 'nullable|array|max:30',
            'pain_points.*' => 'string|max:255',
            'needs' => 'nullable|array|max:30',
            'needs.*' => 'string|max:255',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'job_titles' => 'nullable|array|max:30',
            'job_titles.*' => 'string|max:100',
        ]);

        $dto = SaveAudienceData::fromArray($validated);
        $audience = $this->brandService->saveAudience($this->getContext($request), $dto, $id);

        return ApiResponse::success(
            data: ['audience' => new AudienceResource($audience)],
            meta: ['message' => 'Audience profile updated.']
        );
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
        $voice = $brain['profile']?->voice;

        return ApiResponse::success(['voice' => $voice ? new BrandVoiceResource($voice) : null]);
    }

    public function saveVoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_tones' => 'nullable|array|max:10',
            'primary_tones.*' => 'string|max:50',
            'formality_scale' => 'nullable|integer|min:1|max:5',
            'playfulness_scale' => 'nullable|integer|min:1|max:5',
            'boldness_scale' => 'nullable|integer|min:1|max:5',
            'simplicity_scale' => 'nullable|integer|min:1|max:5',
            'preferred_phrases' => 'nullable|array|max:50',
            'preferred_phrases.*' => 'string|max:150',
            'forbidden_phrases' => 'nullable|array|max:50',
            'forbidden_phrases.*' => 'string|max:150',
            'words_to_avoid' => 'nullable|array|max:50',
            'words_to_avoid.*' => 'string|max:100',
            'words_to_emphasize' => 'nullable|array|max:50',
            'words_to_emphasize.*' => 'string|max:100',
            'cta_preferences' => 'nullable|array|max:20',
            'cta_preferences.*' => 'string|max:150',
            'emoji_style' => 'nullable|string|in:none,minimal,moderate,expressive',
            'hashtag_style' => 'nullable|string|max:30',
            'dialect' => 'nullable|string|max:50',
        ]);

        $dto = SaveBrandVoiceData::fromArray($validated);
        $voice = $this->brandService->saveBrandVoice($this->getContext($request), $dto);

        return ApiResponse::success(['voice' => new BrandVoiceResource($voice)], ['message' => 'Brand voice updated successfully.']);
    }

    /**
     * Goals CRUD
     */
    public function listGoals(Request $request): JsonResponse
    {
        $goals = $this->brandService->listGoals($this->getContext($request));
        return ApiResponse::success(['goals' => GoalResource::collection($goals)]);
    }

    public function storeGoal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'goal_type' => 'required|string|max:50',
            'priority' => 'required|string|in:primary,secondary,tertiary',
            'description' => 'nullable|string|max:1000',
            'target_metrics' => 'nullable|array|max:20',
            'target_metrics.*' => 'string|max:100',
        ]);

        $dto = SaveGoalData::fromArray($validated);
        $goal = $this->brandService->saveGoal($this->getContext($request), $dto);

        return ApiResponse::success(['goal' => new GoalResource($goal)], ['message' => 'Goal created.'], 201);
    }

    public function updateGoal(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'goal_type' => 'sometimes|string|max:50',
            'priority' => 'sometimes|string|in:primary,secondary,tertiary',
            'description' => 'nullable|string|max:1000',
            'target_metrics' => 'nullable|array|max:20',
            'target_metrics.*' => 'string|max:100',
        ]);

        $dto = SaveGoalData::fromArray($validated);
        $goal = $this->brandService->saveGoal($this->getContext($request), $dto, $id);

        return ApiResponse::success(['goal' => new GoalResource($goal)], ['message' => 'Goal updated.']);
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
        $competitors = $this->brandService->listCompetitors($this->getContext($request));
        return ApiResponse::success(['competitors' => CompetitorResource::collection($competitors)]);
    }

    public function storeCompetitor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'website' => ['nullable', 'string', 'max:255', 'regex:' . self::SAFE_URL_REGEX],
            'description' => 'nullable|string|max:1000',
            'positioning' => 'nullable|string|max:500',
            'strengths' => 'nullable|array|max:20',
            'strengths.*' => 'string|max:150',
            'weaknesses' => 'nullable|array|max:20',
            'weaknesses.*' => 'string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $dto = SaveCompetitorData::fromArray($validated);
        $competitor = $this->brandService->saveCompetitor($this->getContext($request), $dto);

        return ApiResponse::success(['competitor' => new CompetitorResource($competitor)], ['message' => 'Competitor added.'], 201);
    }

    public function updateCompetitor(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'website' => ['nullable', 'string', 'max:255', 'regex:' . self::SAFE_URL_REGEX],
            'description' => 'nullable|string|max:1000',
            'positioning' => 'nullable|string|max:500',
            'strengths' => 'nullable|array|max:20',
            'strengths.*' => 'string|max:150',
            'weaknesses' => 'nullable|array|max:20',
            'weaknesses.*' => 'string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $dto = SaveCompetitorData::fromArray($validated);
        $competitor = $this->brandService->saveCompetitor($this->getContext($request), $dto, $id);

        return ApiResponse::success(['competitor' => new CompetitorResource($competitor)], ['message' => 'Competitor updated.']);
    }

    public function deleteCompetitor(Request $request, int $id): JsonResponse
    {
        $this->brandService->deleteCompetitor($this->getContext($request), $id);
        return ApiResponse::success(null, ['message' => 'Competitor removed.']);
    }

    /**
     * Preview Sanitized AI Brand Context for the current tenant.
     * Note: Internal system prompts and instructions are NEVER exposed publicly.
     */
    public function aiContext(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task' => 'nullable|string|in:content_generation,social_post,campaign,marketing_strategy',
            'audience_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
            'platform' => 'nullable|string|in:linkedin,instagram,x,tiktok,facebook,youtube',
        ]);

        $task = $validated['task'] ?? 'content_generation';
        $audienceId = isset($validated['audience_id']) ? (int) $validated['audience_id'] : null;
        $productId = isset($validated['product_id']) ? (int) $validated['product_id'] : null;
        $platform = $validated['platform'] ?? null;

        $context = $this->brandService->getAIBrandContext($this->getContext($request), $audienceId, $productId);

        $minimized = match ($task) {
            'content_generation', 'social_post' => $context->forContentGeneration($audienceId, $productId, $platform),
            default => $context->toArray(),
        };

        // NEVER expose internal system prompt / instructions to frontend
        return ApiResponse::success([
            'context' => $minimized,
        ]);
    }
}
