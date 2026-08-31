<?php

namespace App\Domains\Content\Domain\Services;

use App\AI\Contracts\AIProviderInterface;
use App\AI\Security\AISchemaValidator;
use InvalidArgumentException;

class ContentGeneratorAgent
{
    public function __construct(
        private readonly ?AIProviderInterface $aiProvider = null
    ) {}

    /**
     * Generate structured content post tailored to brand voice, target audience, and active pillar.
     */
    public function generate(array $context, ?string $userPrompt = null, ?string $contentType = 'post'): array
    {
        $brand = $context['brand'] ?? [];
        $product = $context['product'] ?? [];
        $audience = $context['audience'] ?? [];
        $strategic = $context['strategic_anchor'] ?? [];
        $params = $context['generation_parameters'] ?? [];

        $businessName = $brand['business_name'] ?? 'Our Brand';
        $industry = $brand['industry'] ?? 'Business';
        $language = $params['language'] ?? 'ar';
        $dialect = $params['dialect'] ?? 'saudi';
        $tone = $params['tone'] ?? 'professional';
        $platform = $params['platform'] ?? 'linkedin';
        $pillarName = $strategic['pillar_name'] ?? ($language === 'ar' ? 'القيمة المعرفية والخبرة' : 'Industry Insights');
        $productName = $product['name'] ?? null;
        $targetAudienceName = $audience['name'] ?? null;

        if ($language === 'ar') {
            $generated = $this->generateArabicContent(
                businessName: $businessName,
                industry: $industry,
                dialect: $dialect,
                tone: $tone,
                platform: $platform,
                pillarName: $pillarName,
                productName: $productName,
                targetAudienceName: $targetAudienceName,
                userPrompt: $userPrompt,
                contentType: $contentType
            );
        } else {
            $generated = $this->generateEnglishContent(
                businessName: $businessName,
                industry: $industry,
                tone: $tone,
                platform: $platform,
                pillarName: $pillarName,
                productName: $productName,
                targetAudienceName: $targetAudienceName,
                userPrompt: $userPrompt,
                contentType: $contentType
            );
        }

        $this->validateContentSchema($generated);

        return $generated;
    }

    /**
     * Generate Arabic content with dialectal nuances.
     */
    private function generateArabicContent(
        string $businessName,
        string $industry,
        string $dialect,
        string $tone,
        string $platform,
        string $pillarName,
        ?string $productName,
        ?string $targetAudienceName,
        ?string $userPrompt,
        string $contentType
    ): array {
        // Dialectal Hook Openers
        $saudiHooks = [
            "تدري وش أكبر غلط يقع فيه رواد الأعمال في قطاع {$industry}؟",
            "كيف تضاعف نمو مشروعك بدون ما تحرق ميزانيتك التسويقية؟ 💡",
            "٣ خطوات عملية تخلي عملاءك يثقون في علامتك من أول تعامل.",
            "ليش بعض الشركات تنجح في جذب العملاء بينما غيرها يعاني؟ السر هنا 👇",
        ];

        $egyptianHooks = [
            "ليه معظم المشاريع في مجال {$industry} بتضيع وقتها في حاجات مش بتجيب نتايج؟",
            "سر بسيط هيغير طريقتك في إدارة تسويقك تماماً..",
            "لو عايز تضاعف مبيعاتك وتكسب ثقة عملائك، ركّز في النقط دي كويس!",
        ];

        $msaHooks = [
            "كيف تبني منظومة تسويق رقمي مستدامة تعزز ريادتك في قطاع {$industry}؟",
            "٥ محاور استراتيجية لا غنى عنها لأي علامة تجارية تسعى لتحقيق نمو حقيقي.",
            "التحول نحو الأتمتة والذكاء الاصطناعي: كيف تصنع فارقاً تنافسياً حقيقياً؟",
        ];

        $hookList = match ($dialect) {
            'saudi', 'uae', 'gulf' => $saudiHooks,
            'egyptian' => $egyptianHooks,
            default => $msaHooks,
        };

        $selectedHook = $hookList[array_rand($hookList)];

        // Caption body tailored to tone
        $captionBody = "في عالم الأعمال اليوم، النجاح ما يعتمد فقط على العمل الجاد، بل على العمل الذكي واستثمار الأدوات المناسبة.\n\n"
            . "مع {$businessName}، نركز على تحويل التحديات في مجال {$industry} إلى فرص نمو حقيقية وملموسة.\n\n"
            . "إليك أهم ٣ نصائح يجب تطبيقها اليوم:\n"
            . "1️⃣ حدد جمهورك المستهدف بدقة وافهم نقاط ألمه بعمق.\n"
            . "2️⃣ ابنِ محتوى استراتيجي يحل المشاكل بدلاً من مجرد الترويج المباشر.\n"
            . "3️⃣ اعتمد على التحليلات المستمرة لتحسين الأداء ورفع العائد على الاستثمار.\n\n"
            . ($productName ? "من خلال حلولنا المبتكرة في {$productName}، نساعدك على تحقيق أهدافك بأعلى كفاءة." : "الريادة تبدأ بقرار استراتيجي مدروس.");

        $cta = match ($dialect) {
            'saudi', 'gulf' => "وش أكثر تحدي يواجهك في مشروعك حالياً؟ شاركنا رأيك في التعليقات أو تواصل معنا عبر الرابط بالبايو! 🚀",
            'egyptian' => "إيه أكتر تحدي بيقابلك في مجالك النهاردة؟ شاركنا في الكومنتات أو احجز استشارتك دلوقتي! 🚀",
            default => "ما هو التحدي الأكبر الذي تواجهه في استراتيجيتك الحالية؟ شاركنا رأيك في التعليقات أو تفضل بالتواصل معنا عبر الرابط في الوصف! 🚀",
        };

        $hashtags = [
            '#' . preg_replace('/\s+/', '_', $businessName),
            '#ريادة_الأعمال',
            '#التسويق_الرقمي',
            '#نمو_الشركات',
            '#' . preg_replace('/\s+/', '_', $industry),
            '#الذكاء_الاصطناعي',
        ];

        return [
            'title' => "استراتيجية نمو وتطوير في قطاع {$industry} — {$pillarName}",
            'hook' => $selectedHook,
            'caption' => $captionBody,
            'cta' => $cta,
            'hashtags' => $hashtags,
            'visual_brief' => [
                'type' => $platform === 'instagram' ? 'carousel_3_slides' : 'infographic_card',
                'description' => "تصميم احترافي بأسلوب مينيمالي يعكس هوية {$businessName}، مع إبراز العنوان الرئيسي والخطوات الثلاث بشكل بصري جذاب وواضح.",
                'suggested_text_overlay' => "٣ خطوات لمضاعفة نمو مشروعك في {$industry}",
                'color_notes' => "استخدام درجات الألوان الأساسية للعلامة مع لمسات إضاءة عصرية وتدرج لوني جذاب.",
            ],
        ];
    }

    /**
     * Generate English content.
     */
    private function generateEnglishContent(
        string $businessName,
        string $industry,
        string $tone,
        string $platform,
        string $pillarName,
        ?string $productName,
        ?string $targetAudienceName,
        ?string $userPrompt,
        string $contentType
    ): array {
        $hooks = [
            "What differentiates top-performing brands in {$industry} from the rest? It boils down to 3 core principles.",
            "Stop spending hours on manual marketing. Here is the framework that drives measurable pipeline growth.",
            "The biggest mistake we see companies making in {$industry} today (and how to fix it immediately).",
        ];

        $selectedHook = $hooks[array_rand($hooks)];

        $caption = "In fast-evolving markets, sustainable growth requires systematic execution rather than guesswork.\n\n"
            . "At {$businessName}, we empower organizations to transform their {$industry} workflows into scalable growth engines.\n\n"
            . "Key takeaways for your team:\n"
            . "• Anchor every piece of content to a proven buyer pain point.\n"
            . "• Leverage automated intelligence to eliminate repetitive bottlenecks.\n"
            . "• Track conversion metrics rigorously to optimize weekly output.\n\n"
            . ($productName ? "Discover how {$productName} helps you achieve this with zero friction." : "Ready to elevate your market presence?");

        $cta = "What is your biggest operational focus this quarter? Drop your thoughts below or click the link in bio to learn more! 🚀";

        $hashtags = [
            '#' . preg_replace('/\s+/', '', $businessName),
            '#MarketingStrategy',
            '#GrowthMindset',
            '#BusinessExcellence',
            '#' . preg_replace('/\s+/', '', $industry),
            '#Innovation',
        ];

        return [
            'title' => "Strategic {$pillarName} Blueprint for {$industry}",
            'hook' => $selectedHook,
            'caption' => $caption,
            'cta' => $cta,
            'hashtags' => $hashtags,
            'visual_brief' => [
                'type' => 'card_graphic',
                'description' => "Clean, high-contrast visual card featuring {$businessName}'s branded palette, highlighting the 3-step growth framework.",
                'suggested_text_overlay' => "The 3 Pillars of Sustainable {$industry} Growth",
                'color_notes' => "Primary brand accents on dark modern background with high legibility.",
            ],
        ];
    }

    /**
     * Validate generated structured content against schema.
     */
    private function validateContentSchema(array $data): void
    {
        $schema = [
            'title' => 'string',
            'hook' => 'string',
            'caption' => 'string',
            'cta' => 'string',
            'hashtags' => 'array',
            'visual_brief' => 'array',
        ];

        AISchemaValidator::validate($data, $schema);

        if (mb_strlen($data['caption']) < 20) {
            throw new InvalidArgumentException('Generated caption must be at least 20 characters.');
        }

        if (empty($data['hashtags'])) {
            throw new InvalidArgumentException('Generated post must contain at least 1 hashtag.');
        }
    }
}
