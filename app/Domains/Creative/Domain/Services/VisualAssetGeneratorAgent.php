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
                    $this->compositeBrandOverlay(
                        storagePath: $aiResult->data['file_path'],
                        orgId: $orgId,
                        brandId: $brandId,
                        businessName: $businessName,
                        palette: $palette
                    );

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

                Log::warning('VisualAssetGeneratorAgent: Primary AI image generation failed, trying fallback provider', [
                    'error' => $aiResult->errorMessage,
                ]);
                $fallbackReason = $aiResult->errorMessage;
            } catch (\Throwable $e) {
                Log::warning('VisualAssetGeneratorAgent: Exception during primary AI image generation', [
                    'error' => $e->getMessage(),
                ]);
                $fallbackReason = $e->getMessage();
            }
        } else {
            $fallbackReason = 'Primary AI image provider is not configured.';
        }

        // 1b. Multi-Provider Fallback: If primary failed or not configured, try OpenAI if available
        if (!empty($aiPrompt)) {
            try {
                $openAiKey = null;
                if (!empty($orgId) && is_numeric($orgId)) {
                    $org = \App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel::find($orgId);
                    $openAiKey = $org?->ai_config_json['openai_api_key'] ?? null;
                }
                $openAiKey = $openAiKey ?: config('services.openai.api_key');

                if (!empty($openAiKey)) {
                    $openAiProvider = new \App\AI\Providers\OpenAIAIProvider(apiKey: $openAiKey);
                    $aiResult = $openAiProvider->generateImage($aiPrompt, [
                        'aspect_ratio' => $aspectRatio,
                        'org_id' => $orgId,
                        'brand_id' => $brandId,
                    ]);

                    if ($aiResult->success && !empty($aiResult->data['file_path'])) {
                        $this->compositeBrandOverlay(
                            storagePath: $aiResult->data['file_path'],
                            orgId: $orgId,
                            brandId: $brandId,
                            businessName: $businessName,
                            palette: $palette
                        );

                        return [
                            'file_name' => $aiResult->data['file_name'],
                            'file_path' => $aiResult->data['file_path'],
                            'file_type' => 'image',
                            'mime_type' => $aiResult->data['mime_type'] ?? 'image/png',
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
                }
            } catch (\Throwable $e) {
                Log::warning('VisualAssetGeneratorAgent: Secondary OpenAI fallback exception', ['error' => $e->getMessage()]);
            }
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
            textMuted: $textMuted,
            orgId: $orgId,
            brandId: $brandId
        );

        $fileName = 'asset_' . uniqid() . '_' . str_replace(':', 'x', $aspectRatio) . '.svg';
        $storageDir = "creative-assets/{$orgId}/{$brandId}";
        $storagePath = "{$storageDir}/{$fileName}";

        try {
            Storage::disk('public')->put($storagePath, $svgMarkup);
            $publicUrl = '/storage/' . ltrim($storagePath, '/');
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
     * Composite real brand logo onto the generated image with GD.
     */
    private function compositeBrandOverlay(
        string $storagePath,
        int|string $orgId,
        int|string $brandId,
        string $businessName,
        array $palette
    ): void {
        if (!extension_loaded('gd')) {
            return;
        }

        try {
            $logoAsset = null;
            if (is_numeric($orgId)) {
                $logoQuery = \App\Domains\Brand\Infrastructure\Persistence\Models\BrandAssetModel::where('organization_id', (int) $orgId)
                    ->where('type', 'logo');
                if (is_numeric($brandId)) {
                    $logoQuery->where('brand_profile_id', (int) $brandId);
                }
                $logoAsset = $logoQuery->first();
                if (!$logoAsset && is_numeric($orgId)) {
                    $logoAsset = \App\Domains\Brand\Infrastructure\Persistence\Models\BrandAssetModel::where('organization_id', (int) $orgId)
                        ->where('type', 'logo')
                        ->first();
                }
            }

            $imageDiskPath = Storage::disk('public')->path($storagePath);
            if (!file_exists($imageDiskPath)) {
                return;
            }

            $imgData = file_get_contents($imageDiskPath);
            $mainImg = @imagecreatefromstring($imgData);
            if (!$mainImg) {
                return;
            }

            imagealphablending($mainImg, true);
            imagesavealpha($mainImg, true);

            $imgW = imagesx($mainImg);
            $imgH = imagesy($mainImg);

            // 1. If Brand Logo exists, overlay it in the top corner with a luxury glassmorphism badge backing
            if ($logoAsset && Storage::disk('public')->exists($logoAsset->file_path)) {
                $logoDiskPath = Storage::disk('public')->path($logoAsset->file_path);
                $logoData = file_get_contents($logoDiskPath);
                $logoImg = @imagecreatefromstring($logoData);

                if ($logoImg) {
                    imagealphablending($logoImg, true);
                    $logoW = imagesx($logoImg);
                    $logoH = imagesy($logoImg);

                    $targetW = (int) ($imgW * 0.16);
                    $targetH = (int) ($logoH * ($targetW / max(1, $logoW)));
                    if ($targetH > 90) {
                        $targetH = 90;
                        $targetW = (int) ($logoW * ($targetH / max(1, $logoH)));
                    }

                    // Place in top-right corner with 36px margin
                    $destX = $imgW - $targetW - 40;
                    $destY = 40;

                    // Draw subtle frosted glass badge backing for logo
                    $badgePad = 12;
                    $badgeX1 = $destX - $badgePad;
                    $badgeY1 = $destY - $badgePad;
                    $badgeX2 = $destX + $targetW + $badgePad;
                    $badgeY2 = $destY + $targetH + $badgePad;

                    $glassColor = imagecolorallocatealpha($mainImg, 2, 6, 23, 50); // Deep dark slate with 60% opacity
                    imagefilledrectangle($mainImg, $badgeX1, $badgeY1, $badgeX2, $badgeY2, $glassColor);

                    // Copy logo with high quality interpolation
                    imagecopyresampled($mainImg, $logoImg, $destX, $destY, 0, 0, $targetW, $targetH, $logoW, $logoH);
                    imagedestroy($logoImg);
                }
            }

            // Save composited image back to disk
            if (str_ends_with(strtolower($storagePath), '.jpg') || str_ends_with(strtolower($storagePath), '.jpeg')) {
                imagejpeg($mainImg, $imageDiskPath, 92);
            } else {
                imagepng($mainImg, $imageDiskPath, 8);
            }

            imagedestroy($mainImg);
        } catch (\Throwable $e) {
            Log::warning('VisualAssetGeneratorAgent: Logo compositing skipped', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Render modern, high-contrast SVG banner card with brand assets and luxury layout.
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
        string $textMuted,
        int|string $orgId = 'default',
        int|string $brandId = 'default'
    ): string {
        $cardWidth = (int) ($width * 0.90);
        $cardHeight = (int) ($height * 0.82);
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

        // Try to get base64 encoded brand logo if available
        $logoSvgTag = '';
        if (is_numeric($orgId)) {
            try {
                $logoQuery = \App\Domains\Brand\Infrastructure\Persistence\Models\BrandAssetModel::where('organization_id', (int) $orgId)
                    ->where('type', 'logo');
                if (is_numeric($brandId)) {
                    $logoQuery->where('brand_profile_id', (int) $brandId);
                }
                $logo = $logoQuery->first() ?? \App\Domains\Brand\Infrastructure\Persistence\Models\BrandAssetModel::where('organization_id', (int) $orgId)->where('type', 'logo')->first();
                if ($logo && Storage::disk('public')->exists($logo->file_path)) {
                    $logoBytes = Storage::disk('public')->get($logo->file_path);
                    $mime = $logo->mime_type ?: 'image/png';
                    $base64Logo = base64_encode($logoBytes);
                    $logoX = $cardX + $cardWidth - 190;
                    $logoSvgTag = "<image x=\"{$logoX}\" y=\"{$badgeY}\" width=\"140\" height=\"48\" href=\"data:{$mime};base64,{$base64Logo}\" preserveAspectRatio=\"xMidYMid meet\" />";
                }
            } catch (\Throwable $e) {}
        }

        $tspanLines = '';
        foreach ($lines as $i => $line) {
            $tspanY = $textYStart + ($i * 54);
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
      <feDropShadow dx="0" dy="24" stdDeviation="32" flood-color="{$primaryColor}" flood-opacity="0.2" />
    </filter>
  </defs>

  <!-- Background Base -->
  <rect width="{$width}" height="{$height}" fill="url(#bgGrad)" />

  <!-- Ambient Glow Circles -->
  <circle cx="{$width}" cy="0" r="400" fill="{$primaryColor}" opacity="0.16" filter="blur(80px)" />
  <circle cx="0" cy="{$height}" r="400" fill="{$primaryColor}" opacity="0.10" filter="blur(80px)" />

  <!-- Central Card Box -->
  <rect x="{$cardX}" y="{$cardY}" width="{$cardWidth}" height="{$cardHeight}" rx="36" fill="{$cardBg}" fill-opacity="0.85" stroke="{$primaryColor}" stroke-opacity="0.35" stroke-width="2" filter="url(#cardGlow)" />

  <!-- Brand Badge Top -->
  <g transform="translate({$badgeX}, {$badgeY})">
    <rect width="200" height="44" rx="22" fill="{$primaryColor}" fill-opacity="0.15" stroke="{$primaryColor}" stroke-opacity="0.45" stroke-width="1.5" />
    <text x="100" y="27" fill="{$primaryColor}" font-family="'Cairo', 'Inter', system-ui, sans-serif" font-size="15" font-weight="800" text-anchor="middle">{$businessName}</text>
  </g>

  {$logoSvgTag}

  <!-- Category Title -->
  <text x="{$centerX}" y="{$titleY}" fill="{$textMuted}" font-family="'Cairo', 'Inter', system-ui, sans-serif" font-size="16" font-weight="600" text-anchor="middle" letter-spacing="1">{$title}</text>

  <!-- Central Hook / Quote Text -->
  <text font-family="'Cairo', 'Alexandria', 'Inter', system-ui, sans-serif" font-size="38" font-weight="800" fill="{$textPrimary}" text-anchor="middle" letter-spacing="-0.5">
    {$tspanLines}
  </text>

  <!-- Bottom Accent / CTA Bar -->
  <g transform="translate({$badgeX}, {$footerY})">
    <circle cx="16" cy="16" r="16" fill="url(#primaryGrad)" />
    <text x="44" y="21" fill="{$textMuted}" font-family="'Cairo', 'Inter', system-ui, sans-serif" font-size="13" font-weight="600">هوية معتمدة • {$businessName}</text>
  </g>
</svg>
SVG;
    }
}
