<?php

namespace App\Domains\Creative\Domain\Services;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;

class VisualPromptBuilder
{
    /**
     * Build rich, bounded visual parameters grounded in Brand Brain.
     */
    public function build(
        int $organizationId,
        ?string $title = null,
        ?string $hook = null,
        ?string $visualStyle = 'branded_quote',
        ?string $aspectRatio = '1:1',
        ?array $customPalette = null
    ): array {
        $profile = BrandProfileModel::where('organization_id', $organizationId)->first();
        $voice = BrandVoiceModel::where('organization_id', $organizationId)->first();

        $businessName = $profile?->business_name ?? 'Marketly AI';
        $industry = $profile?->industry ?? 'Technology & SaaS';

        // Default or Brand Palette
        $palette = $customPalette ?: [
            'primary' => '#10b981', // Emerald 500
            'secondary' => '#064e3b', // Emerald 900
            'accent' => '#34d399', // Emerald 400
            'background' => '#020617', // Slate 950
            'card_bg' => '#0f172a', // Slate 900
            'text_primary' => '#ffffff',
            'text_muted' => '#94a3b8',
        ];

        $dimensions = match ($aspectRatio) {
            '4:5' => ['width' => 1080, 'height' => 1350, 'label' => 'Instagram Portrait (4:5)'],
            '9:16' => ['width' => 1080, 'height' => 1920, 'label' => 'Story & Reel (9:16)'],
            '16:9' => ['width' => 1200, 'height' => 675, 'label' => 'Landscape / Twitter Card (16:9)'],
            default => ['width' => 1080, 'height' => 1080, 'label' => 'Square (1:1)'],
        };

        $aiPrompt = "Professional, minimalist, award-winning social media marketing graphic for {$businessName} ({$industry}). "
            . "Visual style: {$visualStyle}. Color harmony: {$palette['primary']} emerald tones with {$palette['background']} dark mode aesthetic. "
            . "High contrast typography, modern corporate layout, subtle glassmorphism highlights, 8k resolution, photorealistic studio lighting.";

        return [
            'business_name' => $businessName,
            'industry' => $industry,
            'title' => $title ?: "Growth Strategy for {$industry}",
            'hook' => $hook ?: 'Transform your marketing operations with autonomous AI execution.',
            'visual_style' => $visualStyle,
            'aspect_ratio' => $aspectRatio,
            'dimensions' => $dimensions,
            'color_palette' => $palette,
            'ai_prompt' => $aiPrompt,
            'negative_prompt' => 'low quality, blurry, distorted text, cluttered layout, amateur design, watermark, pixelated',
        ];
    }
}
