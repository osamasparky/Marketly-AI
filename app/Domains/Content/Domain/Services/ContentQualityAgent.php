<?php

namespace App\Domains\Content\Domain\Services;

class ContentQualityAgent
{
    /**
     * Perform automated compliance, quality, and brand-consistency audit.
     */
    public function audit(array $postData, array $brandGuidelines = []): array
    {
        $strengths = [];
        $warnings = [];
        $suggestions = [];

        $caption = $postData['caption'] ?? '';
        $hook = $postData['hook'] ?? '';
        $cta = $postData['cta'] ?? '';
        $hashtags = $postData['hashtags'] ?? [];
        $fullText = "{$hook} {$caption} {$cta}";

        // 1. Safety & Restrictions Compliance (25%)
        $passedRestrictions = true;
        $safetyScore = 100;
        $blacklist = $brandGuidelines['vocabulary_blacklist'] ?? [];
        $restrictions = $brandGuidelines['restrictions'] ?? [];

        foreach (array_merge($blacklist, $restrictions) as $forbidden) {
            if (!empty($forbidden) && mb_stripos($fullText, $forbidden) !== false) {
                $passedRestrictions = false;
                $safetyScore -= 30;
                $warnings[] = "المنشور يحتوي على عبارة أو كلمة مقيدة للعلامة: '{$forbidden}'";
            }
        }
        $safetyScore = max(0, $safetyScore);

        if ($passedRestrictions) {
            $strengths[] = 'المنشور متوافق 100% مع قيود ومحددات العلامة التجارية.';
        }

        // 2. Hook Strength (25%)
        $hookScore = 70;
        if (!empty($hook)) {
            $hookLen = mb_strlen($hook);
            if ($hookLen >= 15 && $hookLen <= 120) {
                $hookScore += 15;
                $strengths[] = 'افتتاحية (Hook) قوية وموجزة ومثيرة لاهتمام القارئ.';
            }
            if (preg_match('/[؟?؟!]/u', $hook)) {
                $hookScore += 15;
                $strengths[] = 'استخدام أسلوب التساؤل أو الإثارة في السطر الأول لرفع معدل القراءة.';
            }
        } else {
            $hookScore = 30;
            $warnings[] = 'لا توجد افتتاحية واضحة لجذب الانتباه.';
            $suggestions[] = 'أضف سطر افتتاحية (Hook) مباشر يثير فضول القارئ في أول ثانيتين.';
        }
        $hookScore = min(100, max(0, $hookScore));

        // 3. Clarity & Readability (25%)
        $clarityScore = 75;
        $paragraphs = array_filter(explode("\n", $caption), fn($p) => trim($p) !== '');
        if (count($paragraphs) >= 3) {
            $clarityScore += 15;
            $strengths[] = 'تنسيق ممتاز مع فواصل أسطر مريحة للعين وتسهل التصفح السريع.';
        } else {
            $clarityScore -= 15;
            $suggestions[] = 'قسّم النص الطويل إلى فقرات أقصر مع استخدام نقاط توضيحية لزيادة القراءة.';
        }

        if (mb_strlen($caption) > 1500) {
            $clarityScore -= 10;
            $warnings[] = 'طول النص قد يكون مفرطاً لبعض المنصات السريعة.';
        }
        $clarityScore = min(100, max(0, $clarityScore));

        // 4. Brand Alignment & CTA (25%)
        $brandScore = 70;
        if (!empty($cta)) {
            $brandScore += 20;
            $strengths[] = 'دعوة واضحة لاتخاذ إجراء (Call To Action) في نهاية المنشور.';
        } else {
            $brandScore -= 20;
            $warnings[] = 'المنشور يفتقر إلى دعوة واضحة لاتخاذ إجراء (CTA).';
            $suggestions[] = 'أضف سؤالاً ختامياً أو رابطاً واضحاً لتوجيه القارئ للخطوة التالية.';
        }

        if (count($hashtags) >= 2 && count($hashtags) <= 8) {
            $brandScore += 10;
        } else {
            $suggestions[] = 'استخدم بين 3 إلى 6 وسوم (Hashtags) مخصصة ومتوازنة.';
        }
        $brandScore = min(100, max(0, $brandScore));

        // Calculate Overall Weighted Score (0-100)
        $overallScore = (int) round(
            ($safetyScore * 0.30) +
            ($hookScore * 0.25) +
            ($clarityScore * 0.20) +
            ($brandScore * 0.25)
        );

        return [
            'score' => min(100, max(0, $overallScore)),
            'brand_alignment_score' => $brandScore,
            'hook_strength_score' => $hookScore,
            'clarity_score' => $clarityScore,
            'safety_compliance_score' => $safetyScore,
            'passed_restrictions' => $passedRestrictions,
            'strengths' => $strengths,
            'warnings' => $warnings,
            'suggestions' => $suggestions,
        ];
    }
}
