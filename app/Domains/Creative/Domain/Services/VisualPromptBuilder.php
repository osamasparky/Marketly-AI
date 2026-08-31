<?php

namespace App\Domains\Creative\Domain\Services;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandAssetModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProductServiceModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Brand\Infrastructure\Persistence\Models\BrandVoiceModel;

class VisualPromptBuilder
{
    /**
     * Build rich, bounded visual parameters with strict brand locking and creative variation.
     */
    public function build(
        int $organizationId,
        ?int $brandProfileId = null,
        ?string $title = null,
        ?string $hook = null,
        ?string $visualStyle = 'product_showcase',
        ?string $aspectRatio = '1:1',
        ?array $customPalette = null,
        ?array $visualBrief = null,
        ?int $productId = null,
        bool $isRegeneration = false,
        ?string $avoidPrompt = null
    ): array {
        $profileQuery = BrandProfileModel::where('organization_id', $organizationId);
        if ($brandProfileId) {
            $profileQuery->where('id', $brandProfileId);
        }
        $profile = $profileQuery->first() ?? BrandProfileModel::where('organization_id', $organizationId)->first();

        $effectiveBrandId = $profile?->id ?? $brandProfileId;

        $voiceQuery = BrandVoiceModel::where('organization_id', $organizationId);
        if ($effectiveBrandId) {
            $voiceQuery->where('brand_profile_id', $effectiveBrandId);
        }
        $voice = $voiceQuery->first() ?? BrandVoiceModel::where('organization_id', $organizationId)->first();

        // 1. Resolve Brand Logo
        $logoQuery = BrandAssetModel::where('organization_id', $organizationId)->where('type', 'logo');
        if ($effectiveBrandId) {
            $logoQuery->where('brand_profile_id', $effectiveBrandId);
        }
        $logoAsset = $logoQuery->first();

        // 2. Resolve Product if specified
        $product = null;
        if ($productId) {
            $product = BrandProductServiceModel::with('images')
                ->where('organization_id', $organizationId)
                ->where('id', $productId)
                ->first();
        }

        $businessName = $profile?->business_name ?? 'Marketly AI';
        $industry = $profile?->industry ?? 'Technology & SaaS';

        // 3. Resolve Palette (Brand Brain Phase D colors with dynamic multi-color defaults)
        $primaryColor = $customPalette['primary'] ?? $profile?->primary_color ?? '#10b981';
        $secondaryColor = $customPalette['secondary'] ?? $profile?->secondary_color ?? '#3b82f6';
        $accentColor = $customPalette['accent'] ?? $profile?->accent_color ?? '#f59e0b';
        $bgColor = $customPalette['background'] ?? $profile?->background_color ?? '#020617';

        $palette = [
            'primary' => $primaryColor,
            'secondary' => $secondaryColor,
            'accent' => $accentColor,
            'background' => $bgColor,
            'card_bg' => $secondaryColor,
            'text_primary' => '#ffffff',
            'text_muted' => '#94a3b8',
        ];

        // 4. Dimensions & Platform Labeling
        $dimensions = match ($aspectRatio) {
            '4:5' => ['width' => 1080, 'height' => 1350, 'label' => 'Instagram Portrait Feed (4:5)'],
            '9:16' => ['width' => 1080, 'height' => 1920, 'label' => 'TikTok / IG Reels / Stories (9:16)'],
            '16:9' => ['width' => 1200, 'height' => 675, 'label' => 'LinkedIn / X / YouTube Landscape (16:9)'],
            default => ['width' => 1080, 'height' => 1080, 'label' => 'Square Multi-Platform (1:1)'],
        };

        // 5. Tone & Personality
        $tones = $voice?->primary_tones ?? ['professional', 'innovative'];
        $toneVibe = implode(', ', $tones);
        $dialect = $voice?->dialect ?? 'gulf';
        $formality = $voice?->formality_scale ?? 3;

        // 6. Style-specific Creative Directives
        $briefDescription = $visualBrief['description'] ?? ($hook ?: $title ?: "Commercial 3D marketing visual for {$industry}");
        $textOverlay = $visualBrief['suggested_text_overlay'] ?? ($hook ?: $title ?: $industry);
        $primaryDesc = $this->describeHexColor($primaryColor);
        $secondaryDesc = $this->describeHexColor($secondaryColor);
        $accentDesc = $this->describeHexColor($accentColor);
        $bgDesc = $this->describeHexColor($bgColor);

        $styleDirective = match ($visualStyle) {
            'product_showcase' => "High-end commercial 3D studio product photography. Hero showcase on a sleek minimalist geometric podium, crisp glass reflections, soft directional volumetric lighting in {$primaryDesc} and {$accentDesc} highlights, deep {$bgDesc} backdrop.",
            'lifestyle_scene' => "Authentic, relatable contemporary Saudi/Gulf business executive environment. Modern architectural interior, warm sunlight beam, subtle {$primaryDesc} ambient lighting, luxury minimalist aesthetic.",
            'promotional_banner' => "Dynamic high-impact advertising graphic with 3D floating geometric glass cards, luminous {$accentDesc} energy trails, deep {$bgDesc} contrast, professional commercial agency finish.",
            'infographic_style', 'infographic_card' => "Futuristic 3D isometric abstract tech nodes, floating glowing glassmorphism geometric panels, volumetric {$secondaryDesc} rim glows, neon {$primaryDesc} and {$accentDesc} specular highlights on deep dark {$bgDesc} background.",
            'quote_card', 'branded_quote', 'card_graphic' => "Editorial luxury graphic with abstract floating 3D organic curves, smooth satin textures, rich diffuse studio lighting blending {$primaryDesc} and {$secondaryDesc} on a sleek dark canvas.",
            default => "Award-winning commercial advertising visual with balanced composition, luxury 3D lighting, and polished studio finish."
        };

        // 7. Product grounding
        $productContext = '';
        if ($product) {
            $productContext = " Featured Product/Service Focus: {$product->name} ({$product->category}) - {$product->description}.";
        }

        // 8. Assemble Ultra-High-Fidelity Prompt with Structural Brand Locking
        $lockedIdentity = "=== LOCKED BRAND IDENTITY (STRICT CONSTRAINTS) ===\n"
            . "- Brand Reference: {$businessName} | Industry Focus: {$industry}\n"
            . "- Mandatory 4-Color Harmonic Palette: Primary ({$primaryColor} - {$primaryDesc}), Secondary ({$secondaryColor} - {$secondaryDesc}), Accent ({$accentColor} - {$accentDesc}), Background ({$bgColor} - {$bgDesc}).\n"
            . "- Color Distribution Rule (60-30-10): 60% base deep atmosphere in {$bgDesc} and {$secondaryDesc}, 30% structural 3D objects and reflections in {$primaryDesc}, 10% radiant neon specular highlights in {$accentDesc}.\n"
            . "- Strict Anti-Text & Anti-Logo Rule: DO NOT generate any text, letters, words, brand names, or invented logos in the image pixels. Leave the top corner clean and empty for the official verified logo overlay.";

        $variableComposition = "=== VARIABLE CREATIVE COMPOSITION (CREATIVE FREEDOM) ===\n"
            . "- Visual Concept & Style: {$styleDirective}\n"
            . "- Scene Description: Commercial abstract 3D visual setting for {$industry}\n"
            . "- Multi-Color Lighting Blend: Harmonious volumetric studio lighting combining {$primaryDesc} key light, {$secondaryDesc} rim glow, and {$accentDesc} neon specular accents upon {$bgDesc} backdrop.\n"
            . "{$productContext}\n"
            . "- Technical Specifications: Aspect ratio {$aspectRatio} ({$dimensions['label']}), 8K resolution, octane 3D render, Hasselblad studio lighting, award-winning social media art direction, clean negative space.\n"
            . "- STRICT NEGATIVE PROMPT: text, typography, letters, words, alphabet, numbers, watermark, fake logo, letter M, symbol, messy Arabic font, distorted glyphs, blurry, low resolution.";

        if ($isRegeneration) {
            $variableComposition .= "\n- REGENERATION DIRECTIVE: Alternate dynamic perspective, distinct camera focal depth, and fresh artistic staging. "
                . ($avoidPrompt ? "Avoid previous composition elements." : "Ensure marked difference from previous variation.");
        }

        $cleanPrompt = "{$lockedIdentity}\n\n{$variableComposition}";

        return [
            'organization_id' => $organizationId,
            'brand_profile_id' => $effectiveBrandId,
            'business_name' => $businessName,
            'industry' => $industry,
            'title' => $title ?: "Marketing Graphic for {$businessName}",
            'hook' => $hook ?: $briefDescription,
            'visual_style' => $visualStyle,
            'aspect_ratio' => $aspectRatio,
            'dimensions' => $dimensions,
            'color_palette' => $palette,
            'ai_prompt' => $cleanPrompt,
            'system_prompt' => $cleanPrompt,
            'visual_brief' => $visualBrief,
            'product' => $product,
            'negative_prompt' => 'low quality, blurry, distorted text, amateur design, watermark, grainy, oversaturated artifacts, clashing colors',
        ];
    }

    private function describeHexColor(string $hex): string
    {
        $hex = strtolower(ltrim(trim($hex), '#'));
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return 'emerald green';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        if ($r < 30 && $g < 30 && $b < 40) return 'deep obsidian dark slate';
        if ($r > 200 && $g > 200 && $b > 200) return 'crisp luminous white';
        if ($g > $r && $g > $b) {
            if ($g > 150 && $r < 100) return 'vibrant emerald green';
            if ($g < 100) return 'deep rich forest green';
            return 'electric mint green';
        }
        if ($b > $r && $b > $g) {
            if ($r > 100) return 'royal purple indigo';
            return 'luminous sapphire blue';
        }
        if ($r > $g && $r > $b) {
            if ($g > 120) return 'warm amber gold';
            return 'luxurious crimson ruby';
        }

        return "refined #{$hex} tone";
    }
}

