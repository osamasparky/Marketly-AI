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

        // 3. Resolve Palette (Brand Brain Phase D colors with fallback)
        $primaryColor = $customPalette['primary'] ?? $profile?->primary_color ?? '#10b981';
        $secondaryColor = $customPalette['secondary'] ?? $profile?->secondary_color ?? '#064e3b';
        $accentColor = $customPalette['accent'] ?? $profile?->accent_color ?? '#34d399';
        $bgColor = $customPalette['background'] ?? $profile?->background_color ?? '#020617';

        $palette = [
            'primary' => $primaryColor,
            'secondary' => $secondaryColor,
            'accent' => $accentColor,
            'background' => $bgColor,
            'card_bg' => '#0f172a',
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
        $briefDescription = $visualBrief['description'] ?? ($hook ?: $title ?: "Social media asset for {$businessName}");
        $textOverlay = $visualBrief['suggested_text_overlay'] ?? ($hook ?: $title ?: $businessName);
        $colorNotes = $visualBrief['color_notes'] ?? "Harmonious blending of {$primaryColor} and {$accentColor}";

        $styleDirective = match ($visualStyle) {
            'product_showcase' => "High-end commercial studio product photography. Hero showcase featuring crisp reflections, directional softbox lighting, clean geometric staging with primary accent {$primaryColor} highlights.",
            'lifestyle_scene' => "Authentic, relatable contemporary lifestyle environment tailored for {$dialect} regional business audience. Natural candid lighting, warm premium atmosphere, subtle brand presence.",
            'promotional_banner' => "High-impact promotional campaign banner. Bold typographic hierarchy featuring clear offer callout: '{$textOverlay}', dynamic energy, vibrant contrast using {$primaryColor} and {$accentColor}.",
            'infographic_style' => "Modern analytical infographic with sleek glassmorphism panels, clean data icons, structured step flow, high-contrast dark theme background {$bgColor}.",
            'quote_card', 'branded_quote', 'card_graphic' => "Editorial typography card with elegant layout, modern quotation aesthetic, subtle organic gradient backdrop using {$primaryColor} and {$bgColor}.",
            default => "Professional social media marketing visual with balanced composition and polished commercial finish."
        };

        // 7. Product grounding
        $productContext = '';
        if ($product) {
            $productContext = " Featured Product/Service: {$product->name} ({$product->category}). Description: {$product->description}.";
            if ($product->images->isNotEmpty()) {
                $productContext .= " Product visual reference: {$product->images->first()->name} with authentic styling.";
            }
        }

        // 8. Assemble Prompt Segments
        $lockedIdentity = "=== LOCKED BRAND IDENTITY (STRICT CONSTRAINTS) ===\n"
            . "- Brand: {$businessName} | Industry: {$industry}\n"
            . "- Mandatory Palette: Primary ({$primaryColor}), Secondary ({$secondaryColor}), Accent ({$accentColor}), Background ({$bgColor}).\n"
            . "- Brand Tone: {$toneVibe} (Formality: {$formality}/5).\n"
            . "- Logo & Placement: " . ($logoAsset ? "Incorporate designated top/corner clean zone for {$businessName} logo." : "Reserve minimalist branding zone.") . "\n"
            . "RULE: These brand colors and identity elements MUST appear consistently and harmoniously. Do not deviate from the brand palette.";

        $variableComposition = "=== VARIABLE CREATIVE COMPOSITION (CREATIVE FREEDOM) ===\n"
            . "- Visual Concept & Style: {$styleDirective}\n"
            . "- Scene Description: {$briefDescription}\n"
            . "- Key Visual Focus & Text Focal: {$textOverlay}\n"
            . "- Color Styling Notes: {$colorNotes}\n"
            . "{$productContext}\n"
            . "- Format & Aspect Ratio: {$aspectRatio} ({$dimensions['label']}), 8K resolution, ultra-detailed textures, photorealistic render, award-winning social media marketing design.";

        if ($isRegeneration) {
            $variableComposition .= "\n- REGENERATION DIRECTIVE: Propose a fresh, distinct creative concept and composition with alternative camera angle and visual staging. "
                . ($avoidPrompt ? "Avoid repeating composition: {$avoidPrompt}" : "Ensure marked difference from previous variation.");
        }

        $fullPrompt = "{$lockedIdentity}\n\n{$variableComposition}";

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
            'ai_prompt' => $fullPrompt,
            'system_prompt' => $fullPrompt,
            'visual_brief' => $visualBrief,
            'product' => $product,
            'negative_prompt' => 'low quality, blurry, distorted text, amateur design, watermark, grainy, oversaturated artifacts, clashing colors',
        ];
    }
}

