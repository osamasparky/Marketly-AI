<?php

namespace App\Domains\Creative\Domain\Services;

use App\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VisualAssetGeneratorAgent
{
    public function __construct(
        private readonly ?AIProviderInterface $aiProvider = null
    ) {}

    /**
     * Generate visual marketing asset using AI generation with declared SVG fallback.
     */
    public function generate(array $parameters): array
    {
        $businessName = htmlspecialchars($parameters['business_name'] ?? 'Marketly AI');
        $title = htmlspecialchars($parameters['title'] ?? 'Marketing Insights');
        $hook = htmlspecialchars($parameters['hook'] ?? 'Key takeaways to scale faster.');
        $palette = $parameters['color_palette'] ?? [];
        $dims = $parameters['dimensions'] ?? ['width' => 1080, 'height' => 1080];
        $style = $parameters['visual_style'] ?? 'product_showcase';
        $aspectRatio = $parameters['aspect_ratio'] ?? '1:1';
        $orgId = $parameters['organization_id'] ?? 'default';
        $brandId = $parameters['brand_profile_id'] ?? 'default';
        $aiPrompt = $parameters['ai_prompt'] ?? '';

        $w = (int) $dims['width'];
        $h = (int) $dims['height'];

        $primaryColor = $palette['primary'] ?? '#10b981';
        $bgColor = $palette['background'] ?? '#020617';
        $cardBg = $palette['card_bg'] ?? '#0f172a';
        $textPrimary = $palette['text_primary'] ?? '#ffffff';
        $textMuted = $palette['text_muted'] ?? '#94a3b8';

        // 1. Attempt Real AI Image Generation via connected Provider
        if ($this->aiProvider?->isConfigured() && !empty($aiPrompt)) {
            try {
                $aiResult = $this->aiProvider->generateImage($aiPrompt, [
                    'aspect_ratio' => $aspectRatio,
                    'org_id' => $orgId,
                    'brand_id' => $brandId,
                    'temperature' => 0.85,
                ]);

                if ($aiResult->success && !empty($aiResult->data['file_path'])) {
                    return [
                        'file_name' => $aiResult->data['file_name'],
                        'file_path' => $aiResult->data['file_path'],
                        'file_type' => 'image',
                        'mime_type' => $aiResult->data['mime_type'] ?? 'image/jpeg',
                        'file_size_bytes' => $aiResult->data['file_size_bytes'] ?? 0,
                        'width' => $w,
                        'height' => $h,
                        'aspect_ratio' => $aspectRatio,
                        'visual_style' => $style,
                        'prompt_used' => $aiPrompt,
                        'text_overlay' => $hook,
                        'color_palette' => $palette,
                        'metadata' => [
                            'mode' => 'ai_generated',
                            'image_url' => $aiResult->data['image_url'] ?? null,
                            'visual_brief' => $parameters['visual_brief'] ?? null,
                            'latency_ms' => $aiResult->usage?->latencyMs,
                            'negative_prompt' => $parameters['negative_prompt'] ?? null,
                        ],
                        'status' => 'ready',
                    ];
                }

                Log::warning('VisualAssetGeneratorAgent: AI image generation failed, using declared fallback', [
                    'error' => $aiResult->errorMessage,
                ]);
                $fallbackReason = $aiResult->errorMessage;
            } catch (\Throwable $e) {
                Log::warning('VisualAssetGeneratorAgent: Exception during AI image generation', [
                    'error' => $e->getMessage(),
                ]);
                $fallbackReason = $e->getMessage();
            }
        } else {
            $fallbackReason = 'AI image provider is not configured. Generated using high-contrast branded SVG template.';
        }

        // 2. Declared Fallback: Render crisp SVG Card
        $svgMarkup = $this->renderSvgCard(
            width: $w,
            height: $h,
            businessName: $businessName,
            title: $title,
            hook: $hook,
            style: $style,
            primaryColor: $primaryColor,
            bgColor: $bgColor,
            cardBg: $cardBg,
            textPrimary: $textPrimary,
            textMuted: $textMuted
        );

        $fileName = 'asset_' . uniqid() . '_' . str_replace(':', 'x', $aspectRatio) . '.svg';
        $storageDir = "creative-assets/{$orgId}/{$brandId}";
        $storagePath = "{$storageDir}/{$fileName}";

        try {
            Storage::disk('public')->put($storagePath, $svgMarkup);
            $publicUrl = Storage::disk('public')->url($storagePath);
        } catch (\Throwable $e) {
            $publicUrl = null;
        }

        return [
            'file_name' => $fileName,
            'file_path' => $storagePath,
            'file_type' => 'graphic_card',
            'mime_type' => 'image/svg+xml',
            'file_size_bytes' => strlen($svgMarkup),
            'width' => $w,
            'height' => $h,
            'aspect_ratio' => $aspectRatio,
            'visual_style' => $style,
            'prompt_used' => $aiPrompt,
            'text_overlay' => $hook,
            'color_palette' => $palette,
            'metadata' => [
                'mode' => 'svg_fallback',
                'fallback_reason' => $fallbackReason ?? 'AI Provider offline / fallback mode',
                'svg_markup' => $svgMarkup,
                'image_url' => $publicUrl,
                'visual_brief' => $parameters['visual_brief'] ?? null,
                'layers' => [
                    ['name' => 'Background', 'type' => 'gradient_fill', 'colors' => [$bgColor, $cardBg]],
                    ['name' => 'Brand Header', 'type' => 'text_badge', 'content' => $businessName],
                    ['name' => 'Main Hook', 'type' => 'typography_headline', 'content' => $hook],
                    ['name' => 'Footer CTA', 'type' => 'accent_pill', 'content' => 'Marketly AI Engine'],
                ],
                'negative_prompt' => $parameters['negative_prompt'] ?? null,
            ],
            'status' => 'ready',
        ];
    }

    /**
     * Render modern, high-contrast SVG banner card.
     */
    private function renderSvgCard(
        int $width,
        int $height,
        string $businessName,
        string $title,
        string $hook,
        string $style,
        string $primaryColor,
        string $bgColor,
        string $cardBg,
        string $textPrimary,
        string $textMuted
    ): string {
        $cardWidth = (int) ($width * 0.88);
        $cardHeight = (int) ($height * 0.78);
        $cardX = (int) (($width - $cardWidth) / 2);
        $cardY = (int) (($height - $cardHeight) / 2);

        // Dynamic text wrapping simulation
        $hookWords = explode(' ', $hook);
        $lines = [];
        $currentLine = '';
        foreach ($hookWords as $word) {
            if (mb_strlen($currentLine . ' ' . $word) > 28) {
                $lines[] = trim($currentLine);
                $currentLine = $word;
            } else {
                $currentLine .= ' ' . $word;
            }
        }
        if (!empty($currentLine)) {
            $lines[] = trim($currentLine);
        }
        $lines = array_slice($lines, 0, 4); // Max 4 lines

        $textYStart = (int) ($height / 2) - (count($lines) * 28);
        $centerX = (int) ($width / 2);
        $badgeX = $cardX + 48;
        $badgeY = $cardY + 56;
        $titleY = $cardY + 130;
        $footerY = $cardY + $cardHeight - 70;

        $tspanLines = '';
        foreach ($lines as $i => $line) {
            $tspanY = $textYStart + ($i * 56);
            $tspanLines .= "<tspan x=\"{$centerX}\" y=\"{$tspanY}\">{$line}</tspan>";
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$width} {$height}" width="{$width}" height="{$height}">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$bgColor}" />
      <stop offset="50%" stop-color="{$cardBg}" />
      <stop offset="100%" stop-color="{$bgColor}" />
    </linearGradient>
    <linearGradient id="primaryGrad" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="{$primaryColor}" />
      <stop offset="100%" stop-color="#34d399" />
    </linearGradient>
    <filter id="cardGlow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="24" stdDeviation="32" flood-color="{$primaryColor}" flood-opacity="0.15" />
    </filter>
  </defs>

  <!-- Background Base -->
  <rect width="{$width}" height="{$height}" fill="url(#bgGrad)" />

  <!-- Ambient Glow Circles -->
  <circle cx="{$width}" cy="0" r="350" fill="{$primaryColor}" opacity="0.12" filter="blur(60px)" />
  <circle cx="0" cy="{$height}" r="350" fill="{$primaryColor}" opacity="0.08" filter="blur(60px)" />

  <!-- Central Card Box -->
  <rect x="{$cardX}" y="{$cardY}" width="{$cardWidth}" height="{$cardHeight}" rx="36" fill="{$cardBg}" fill-opacity="0.8" stroke="{$primaryColor}" stroke-opacity="0.3" stroke-width="2" filter="url(#cardGlow)" />

  <!-- Brand Badge Top -->
  <g transform="translate({$badgeX}, {$badgeY})">
    <rect width="180" height="40" rx="20" fill="{$primaryColor}" fill-opacity="0.15" stroke="{$primaryColor}" stroke-opacity="0.4" stroke-width="1.5" />
    <text x="90" y="25" fill="{$primaryColor}" font-family="system-ui, -apple-system, sans-serif" font-size="14" font-weight="700" text-anchor="middle">{$businessName}</text>
  </g>

  <!-- Category Title -->
  <text x="{$badgeX}" y="{$titleY}" fill="{$textMuted}" font-family="system-ui, -apple-system, sans-serif" font-size="16" font-weight="600" letter-spacing="1">{$title}</text>

  <!-- Central Hook / Quote Text -->
  <text font-family="system-ui, -apple-system, 'Cairo', 'Inter', sans-serif" font-size="38" font-weight="800" fill="{$textPrimary}" text-anchor="middle" letter-spacing="-0.5">
    {$tspanLines}
  </text>

  <!-- Bottom Accent / CTA Bar -->
  <g transform="translate({$badgeX}, {$footerY})">
    <circle cx="16" cy="16" r="16" fill="url(#primaryGrad)" />
    <text x="44" y="21" fill="{$textMuted}" font-family="system-ui, -apple-system, sans-serif" font-size="13" font-weight="500">Autonomous Creative Engine • Marketly AI</text>
  </g>
</svg>
SVG;
    }
}
