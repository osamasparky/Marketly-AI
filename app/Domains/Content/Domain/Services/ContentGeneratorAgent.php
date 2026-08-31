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

        if ($this->aiProvider?->isConfigured()) {
            try {
                $aiResult = $this->generateWithAi(
                    context: $context,
                    businessName: $businessName,
                    industry: $industry,
                    language: $language,
                    dialect: $dialect,
                    tone: $tone,
                    platform: $platform,
                    pillarName: $pillarName,
                    productName: $productName,
                    targetAudienceName: $targetAudienceName,
                    userPrompt: $userPrompt,
                    contentType: $contentType
                );

                if ($aiResult !== null) {
                    $this->validateContentSchema($aiResult);
                    return $aiResult;
                }
            } catch (\Throwable $e) {
                // Fallback seamlessly to deterministic generator on AI error
                \Illuminate\Support\Facades\Log::warning('ContentGeneratorAgent AI generation failed, using fallback generator', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Deterministic Fallback Generator
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
     * Generate content post using connected AI Provider (Gemini).
     */
    private function generateWithAi(
        array $context,
        string $businessName,
        string $industry,
        string $language,
        string $dialect,
        string $tone,
        string $platform,
        string $pillarName,
        ?string $productName,
        ?string $targetAudienceName,
        ?string $userPrompt,
        ?string $contentType
    ): ?array {
        $prompt = <<<PROMPT
You are an expert autonomous social media copywriter and growth marketer for the brand "{$businessName}".
Generate a high-converting, authentic, brand-aligned {$contentType} for the platform: {$platform}.

BRAND CONTEXT:
- Business: {$businessName}
- Industry: {$industry}
- Language: {$language}
- Target Dialect/Style: {$dialect}
- Tone of Voice: {$tone}
- Strategic Content Pillar: {$pillarName}
PROMPT;

        if ($productName) {
            $prompt .= "\n- Featured Product/Service: {$productName}";
        }
        if ($targetAudienceName) {
            $prompt .= "\n- Target Audience Persona: {$targetAudienceName}";
        }
        if ($userPrompt) {
            $prompt .= "\n- User Custom Instructions: {$userPrompt}";
        }

        $prompt .= <<<PROMPT


REQUIREMENTS:
1. Language must be in {$language} with authentic {$dialect} nuances (Saudi, Egyptian, Gulf, MSA, or English depending on request).
2. Start with an arresting Hook (first 1-2 lines) tailored to {$platform}.
3. The Caption body must provide genuine educational or practical value, clear formatting, spacing, and appropriate emojis.
4. Conclude with a relevant Call-To-Action (CTA).
5. Provide 3-6 relevant hashtags.
6. Provide a concise visual brief for the graphic designer.

Output must be a JSON object with EXACTLY the following structure:
{
  "title": "Short title describing the post topic",
  "hook": "Engaging hook line",
  "caption": "Full engaging post body text",
  "cta": "Call to action text",
  "hashtags": ["#Tag1", "#Tag2", "#Tag3"],
  "visual_brief": {
    "type": "card_graphic",
    "description": "Visual description",
    "suggested_text_overlay": "Text for image overlay",
    "color_notes": "Color styling suggestions"
  }
}
PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'hook' => ['type' => 'string'],
                'caption' => ['type' => 'string'],
                'cta' => ['type' => 'string'],
                'hashtags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'visual_brief' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'suggested_text_overlay' => ['type' => 'string'],
                        'color_notes' => ['type' => 'string']
                    ],
                    'required' => ['type', 'description']
                ]
            ],
            'required' => ['title', 'hook', 'caption', 'cta', 'hashtags', 'visual_brief']
        ];

        $output = $this->aiProvider->generateStructured($prompt, $schema, [
            'temperature' => 0.7,
            'max_tokens' => 2048,
        ]);

        if (!$output->success || empty($output->data)) {
            return null;
        }

        $data = $output->data;

        // Ensure required fields exist with fallbacks if model omitted any
        if (empty($data['title'])) {
            $data['title'] = "{$pillarName} — {$businessName}";
        }
        if (empty($data['hook'])) {
            $data['hook'] = mb_substr($data['caption'] ?? '', 0, 80);
        }
        if (empty($data['cta'])) {
            $data['cta'] = ($language === 'ar') ? 'شاركنا رأيك في التعليقات 👇' : 'Share your thoughts below! 👇';
        }
        if (empty($data['hashtags']) || !is_array($data['hashtags'])) {
            $data['hashtags'] = ['#' . str_replace(' ', '', $businessName), '#' . str_replace(' ', '', $industry)];
        }
        if (empty($data['visual_brief']) || !is_array($data['visual_brief'])) {
            $data['visual_brief'] = [
                'type' => 'card_graphic',
                'description' => "Visual card for {$businessName} covering {$pillarName}",
                'suggested_text_overlay' => $data['title'],
                'color_notes' => 'Modern dark theme with brand accents',
            ];
        }

        return $data;
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
