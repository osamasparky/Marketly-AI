<?php

namespace App\Domains\Calendar\Domain\Services;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentPillarModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use Carbon\Carbon;

class CalendarPlannerAgent
{
    /**
     * Plan and distribute scheduled posts over a specified horizon (7, 14, 30 days).
     */
    public function plan(int $organizationId, int $daysHorizon = 7, array $options = []): array
    {
        $profile = BrandProfileModel::where('organization_id', $organizationId)->first();
        $voice = BrandVoiceModel::where('organization_id', $organizationId)->first();

        $businessName = $profile?->business_name ?? 'Marketly AI';
        $industry = $profile?->industry ?? 'Technology';
        $dialect = $voice?->dialect ?? 'saudi';
        $tone = $voice?->primary_tones[0] ?? 'professional';

        // Fetch active strategy & pillars
        $strategy = MarketingStrategyModel::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->latest()
            ->first();

        $pillars = $strategy
            ? $strategy->pillars()->where('status', 'active')->get()
            : ContentPillarModel::where('organization_id', $organizationId)->where('status', 'active')->get();

        $pillarList = $pillars->isNotEmpty() ? $pillars->toArray() : [
            ['id' => null, 'name' => 'Industry Leadership & Insights', 'objective' => 'brand_awareness'],
            ['id' => null, 'name' => 'Product Deep Dives & Use Cases', 'objective' => 'education'],
            ['id' => null, 'name' => 'Customer Success & Case Studies', 'objective' => 'sales'],
        ];

        $platforms = $options['platforms'] ?? ['linkedin', 'instagram', 'x', 'tiktok'];
        $postingTimes = ['09:30:00', '13:15:00', '18:45:00', '20:30:00'];

        $plannedSlots = [];
        $startDate = Carbon::tomorrow();

        // Calculate frequency: e.g. 1 post per day for 7/14/30 days
        for ($i = 0; $i < $daysHorizon; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $pillar = $pillarList[$i % count($pillarList)];
            $platform = $platforms[$i % count($platforms)];
            $timeSlot = $postingTimes[$i % count($postingTimes)];
            $scheduledAt = $currentDate->toDateString() . ' ' . $timeSlot;

            $pillarName = $pillar['name'];
            $objective = $pillar['objective'] ?? 'engagement';

            $hook = match ($dialect) {
                'saudi', 'uae', 'gulf' => "خطوة عملية في {$pillarName} لمضاعفة نتائج أعمالك في {$industry} 💡",
                'egyptian' => "سر مهم جداً في {$pillarName} هيفرق في نتائج مشروعك في {$industry} 🚀",
                default => "Strategic insights on {$pillarName} for modern {$industry} teams.",
            };

            $caption = "في هذا المنشور نركز على أهم ممارسات {$pillarName}.\n\n"
                . "١. التخطيط المسبق والتنفيذ المستمر.\n"
                . "٢. التركيز على القيمة الفعلية للعميل.\n"
                . "٣. القياس والتحسين المستمر.\n\n"
                . "شاركنا رأيك وتجربتك في التعليقات!";

            $plannedSlots[] = [
                'day_offset' => $i + 1,
                'scheduled_at' => $scheduledAt,
                'pillar_id' => $pillar['id'] ?? null,
                'strategy_id' => $strategy?->id ?? null,
                'title' => "{$pillarName} — {$currentDate->format('M d')}",
                'hook' => $hook,
                'caption' => $caption,
                'cta' => "شاركنا رأيك في التعليقات أو تفضل بزيارة الرابط بالبايو! 🚀",
                'hashtags' => ['#' . preg_replace('/\s+/', '', $businessName), '#MarketingStrategy', '#Growth'],
                'primary_platform' => $platform,
                'content_type' => $platform === 'tiktok' ? 'reel_script' : 'post',
                'language' => 'ar',
                'dialect' => $dialect,
                'tone' => $tone,
                'objective' => $objective,
                'status' => 'draft',
            ];
        }

        return [
            'horizon_days' => $daysHorizon,
            'total_posts' => count($plannedSlots),
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays($daysHorizon - 1)->toDateString(),
            'slots' => $plannedSlots,
        ];
    }
}
