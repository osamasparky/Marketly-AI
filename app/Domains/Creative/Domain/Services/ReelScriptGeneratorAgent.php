<?php

namespace App\Domains\Creative\Domain\Services;

class ReelScriptGeneratorAgent
{
    /**
     * Generate structured timecoded video Reel script.
     */
    public function generate(array $context, ?string $prompt = null): array
    {
        $businessName = $context['business_name'] ?? 'Marketly AI';
        $title = $context['title'] ?? 'Growth Framework';
        $hook = $context['hook'] ?? 'Stop doing manual marketing in 2026.';
        $dialect = $context['dialect'] ?? 'saudi';

        $isArabic = ($dialect !== 'english');

        if ($isArabic) {
            $scenes = [
                [
                    'scene_number' => 1,
                    'timecode' => '0:00 - 0:03',
                    'role' => 'Hook (جذب الانتباه)',
                    'camera_direction' => 'لقطة قريبة (Close-up) سريعة، حركة ديناميكية إلى الأمام مع نظرة مباشرة للكاميرا.',
                    'spoken_audio' => $hook,
                    'text_overlay' => '🛑 ' . mb_substr($hook, 0, 45) . '..',
                    'sound_effect' => 'صوت تنبيه سريع (Whoosh + Bass hit)',
                ],
                [
                    'scene_number' => 2,
                    'timecode' => '0:03 - 0:15',
                    'role' => 'Problem / Agitation (المشكلة الحقيقية)',
                    'camera_direction' => 'لقطة متوسطة مع تقسيم الشاشة لإظهار فوضى العمل اليدوي مقابل الحل الذكي.',
                    'spoken_audio' => 'أغلب الشركات تضيع أكثر من ٧٠٪ من وقتها في كتابة المنشورات والتعديل اليدوي، بدون استراتيجية واضحة.',
                    'text_overlay' => '📉 ٧٠٪ من الوقت يضيع بدون عائد ملموس!',
                    'sound_effect' => 'موسيقى خلفية متسارعة وخافتة',
                ],
                [
                    'scene_number' => 3,
                    'timecode' => '0:15 - 0:28',
                    'role' => 'The Solution (الحل العملي)',
                    'camera_direction' => 'لقطة شاشة متحركة (B-Roll) تعرض لوحة تحكم ' . $businessName . ' وتوليد الاستراتيجيات والمحتوى التلقائي.',
                    'spoken_audio' => 'مع ' . $businessName . '، علامتك التجارية تبني استراتيجيتها وتنتج محتواها وتنقحه بالكامل بنقرة زر وبلهجتك المفضلة.',
                    'text_overlay' => '✨ أتمتة كاملة بصوت وهوية علامتك',
                    'sound_effect' => 'نغمة تأكيد إيجابية (Success Chime)',
                ],
                [
                    'scene_number' => 4,
                    'timecode' => '0:28 - 0:35',
                    'role' => 'Call To Action (الدعوة للإجراء)',
                    'camera_direction' => 'عودة للمتحدث مع إشارة للأسفل نحو الرابط وظهور شعار العلامة في المنتصف.',
                    'spoken_audio' => 'جرب المنظومة مجاناً اليوم عبر الرابط بالبايو، وخلي التسويق يشتغل بدالك!',
                    'text_overlay' => '🚀 ابدأ تجربتك المجانية الآن بالبايو',
                    'sound_effect' => 'نهاية موسيقية مميزة مع تأثير Pop',
                ],
            ];
        } else {
            $scenes = [
                [
                    'scene_number' => 1,
                    'timecode' => '0:00 - 0:03',
                    'role' => 'Hook (Attention Grabber)',
                    'camera_direction' => 'Tight punch-in close-up with energetic eye contact.',
                    'spoken_audio' => $hook,
                    'text_overlay' => '🛑 ' . mb_substr($hook, 0, 45) . '..',
                    'sound_effect' => 'Fast whoosh + bass impact',
                ],
                [
                    'scene_number' => 2,
                    'timecode' => '0:03 - 0:15',
                    'role' => 'Problem / Agitation',
                    'camera_direction' => 'Split screen demonstrating tedious manual workflows vs autonomous AI pipeline.',
                    'spoken_audio' => 'Most marketing teams spend 70% of their week stuck in manual drafting rather than driving strategic growth.',
                    'text_overlay' => '📉 70% of hours lost to repetitive tasks',
                    'sound_effect' => 'Subtle upbeat tension build',
                ],
                [
                    'scene_number' => 3,
                    'timecode' => '0:15 - 0:28',
                    'role' => 'The Solution',
                    'camera_direction' => 'Sleek UI screencast showing ' . $businessName . ' generating multi-channel campaigns instantly.',
                    'spoken_audio' => 'With ' . $businessName . ', you get an autonomous AI employee that plans, writes, and optimizes 24/7.',
                    'text_overlay' => '✨ Autonomous Brand-Grounded AI Engine',
                    'sound_effect' => 'Modern tech chime',
                ],
                [
                    'scene_number' => 4,
                    'timecode' => '0:28 - 0:35',
                    'role' => 'Call To Action',
                    'camera_direction' => 'Speaker returns with a clean outro badge and link prompt.',
                    'spoken_audio' => 'Start your 14-day free trial today via the link in bio!',
                    'text_overlay' => '🚀 Start Free Trial • Link in Bio',
                    'sound_effect' => 'Brand sonic logo outro',
                ],
            ];
        }

        return [
            'title' => "Reel Script: {$title}",
            'target_duration_seconds' => 35,
            'aspect_ratio' => '9:16',
            'suggested_music_vibe' => 'Modern, energetic, low-fi tech corporate',
            'scenes' => $scenes,
        ];
    }
}
