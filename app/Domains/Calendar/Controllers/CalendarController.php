<?php

namespace App\Domains\Calendar\Controllers;

use App\Domains\Calendar\Application\Services\CalendarApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarApplicationService $calendarService
    ) {}

    /**
     * Get calendar events and metrics within date range.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'platform' => ['nullable', 'string', Rule::in(['linkedin', 'instagram', 'x', 'facebook', 'tiktok'])],
            'status' => ['nullable', 'string', Rule::in(['draft', 'in_review', 'approved', 'scheduled', 'published'])],
        ]);

        $calendarData = $this->calendarService->getCalendar($tenantContext, $filters);

        return response()->json([
            'data' => $calendarData,
        ]);
    }

    /**
     * Auto-generate 7/14/30-day scheduled content calendar plan.
     */
    public function plan(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'horizon_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['string', Rule::in(['linkedin', 'instagram', 'x', 'facebook', 'tiktok'])],
        ]);

        $planResult = $this->calendarService->generatePlan($tenantContext, $params);

        return response()->json([
            'message' => "Successfully planned and scheduled {$planResult['posts_created']} posts over {$planResult['horizon_days']} days.",
            'data' => $planResult,
        ], 201);
    }

    /**
     * Reschedule post (drag-and-drop date/time change).
     */
    public function reschedule(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        $post = $this->calendarService->reschedulePost($tenantContext, $id, $params['scheduled_at']);

        return response()->json([
            'message' => 'Post rescheduled successfully.',
            'data' => $post,
        ]);
    }

    /**
     * Submit post for review.
     */
    public function submitReview(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $post = $this->calendarService->submitReview($tenantContext, $id);

        return response()->json([
            'message' => 'Post submitted for editorial review.',
            'data' => $post,
        ]);
    }

    /**
     * Approve post for scheduling.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $post = $this->calendarService->approvePost($tenantContext, $id);

        return response()->json([
            'message' => 'Post approved successfully.',
            'data' => $post,
        ]);
    }

    /**
     * Schedule approved post.
     */
    public function schedule(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $params = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $post = $this->calendarService->schedulePost($tenantContext, $id, $params['scheduled_at'] ?? null);

        return response()->json([
            'message' => 'Post scheduled successfully.',
            'data' => $post,
        ]);
    }

    /**
     * Unschedule post back to draft.
     */
    public function unschedule(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $post = $this->calendarService->unschedulePost($tenantContext, $id);

        return response()->json([
            'message' => 'Post unscheduled back to draft.',
            'data' => $post,
        ]);
    }
}
