<?php

namespace App\Domains\Content\Domain\Services;

class PlatformRepurposingService
{
    /**
     * Generate platform-specific variations from a core content post.
     *
     * @param array{
     *   title: string,
     *   hook: string,
     *   caption: string,
     *   cta: string,
     *   hashtags: array<string>,
     *   visual_brief: array,
     *   language: string
     * } $postData
     * @param array<string> $targetPlatforms
     * @return array<string, array>
     */
    public function repurpose(array $postData, array $targetPlatforms = ['linkedin', 'instagram', 'x', 'facebook', 'tiktok']): array
    {
        $variations = [];

        foreach ($targetPlatforms as $platform) {
            $variations[$platform] = match ($platform) {
                'linkedin' => $this->formatForLinkedIn($postData),
                'instagram' => $this->formatForInstagram($postData),
                'x' => $this->formatForX($postData),
                'tiktok' => $this->formatForTikTok($postData),
                'facebook' => $this->formatForFacebook($postData),
                default => $this->formatGeneric($postData, $platform),
            };
        }

        return $variations;
    }

    /**
     * Format for LinkedIn (Professional & Thought Leadership).
     */
    private function formatForLinkedIn(array $post): array
    {
        $body = "{$post['hook']}\n\n"
            . "{$post['caption']}\n\n"
            . "👉 {$post['cta']}\n\n"
            . implode(' ', array_slice($post['hashtags'] ?? [], 0, 5));

        return [
            'platform' => 'linkedin',
            'format' => 'post',
            'hook' => $post['hook'],
            'body' => trim($body),
            'cta' => $post['cta'],
            'hashtags' => array_slice($post['hashtags'] ?? [], 0, 5),
            'visual_brief' => $post['visual_brief'] ?? null,
            'thread_slides' => null,
            'character_count' => mb_strlen($body),
            'status' => 'ready',
        ];
    }

    /**
     * Format for Instagram (Engaging Visual & Carousel Ready).
     */
    private function formatForInstagram(array $post): array
    {
        $body = "✨ {$post['hook']}\n\n"
            . "{$post['caption']}\n\n"
            . "💬 {$post['cta']}\n\n"
            . "📌 احفظ المنشور للرجوع إليه لاحقاً | Save for later!\n\n"
            . ".\n.\n.\n"
            . implode(' ', $post['hashtags'] ?? []);

        $slides = [
            [
                'slide_number' => 1,
                'header' => $post['hook'],
                'visual_description' => 'Cover slide with bold typography and brand badge.',
            ],
            [
                'slide_number' => 2,
                'header' => 'الخطوات العملية / The Core Framework',
                'visual_description' => 'Minimalist bullet points with branded icons.',
            ],
            [
                'slide_number' => 3,
                'header' => $post['cta'],
                'visual_description' => 'Call to action slide with follow / save prompts.',
            ],
        ];

        return [
            'platform' => 'instagram',
            'format' => 'carousel',
            'hook' => $post['hook'],
            'body' => trim($body),
            'cta' => $post['cta'],
            'hashtags' => $post['hashtags'] ?? [],
            'visual_brief' => array_merge($post['visual_brief'] ?? [], [
                'aspect_ratio' => '4:5',
                'format' => 'carousel',
            ]),
            'thread_slides' => $slides,
            'character_count' => mb_strlen($body),
            'status' => 'ready',
        ];
    }

    /**
     * Format for X / Twitter (Punchy Under 280 Chars or Thread).
     */
    private function formatForX(array $post): array
    {
        $singleTweet = "{$post['hook']}\n\n" . mb_substr($post['caption'], 0, 140) . "..\n\n" . ($post['hashtags'][0] ?? '#Growth');

        $thread = [
            [
                'tweet_number' => 1,
                'text' => "🧵 {$post['hook']}\n\n👇 تفاصيل سريعة ومهمة في هذه الثريد:",
            ],
            [
                'tweet_number' => 2,
                'text' => mb_substr($post['caption'], 0, 260),
            ],
            [
                'tweet_number' => 3,
                'text' => "🎯 {$post['cta']}\n\n" . implode(' ', array_slice($post['hashtags'] ?? [], 0, 2)),
            ],
        ];

        return [
            'platform' => 'x',
            'format' => 'thread',
            'hook' => $post['hook'],
            'body' => trim($singleTweet),
            'cta' => $post['cta'],
            'hashtags' => array_slice($post['hashtags'] ?? [], 0, 3),
            'visual_brief' => null,
            'thread_slides' => $thread,
            'character_count' => mb_strlen($singleTweet),
            'status' => 'ready',
        ];
    }

    /**
     * Format for TikTok / Reel Script (Video Script Outline).
     */
    private function formatForTikTok(array $post): array
    {
        $script = "🎬 [0:00 - 0:03] HOOK (On-Camera):\n"
            . "\"{$post['hook']}\"\n\n"
            . "💡 [0:03 - 0:25] THE VALUE / PROBLEM:\n"
            . $post['caption'] . "\n\n"
            . "🚀 [0:25 - 0:35] CTA & WRAP UP:\n"
            . "\"{$post['cta']}\"\n\n"
            . "#TikTokMarketing " . implode(' ', array_slice($post['hashtags'] ?? [], 0, 4));

        return [
            'platform' => 'tiktok',
            'format' => 'reel_script',
            'hook' => $post['hook'],
            'body' => trim($script),
            'cta' => $post['cta'],
            'hashtags' => array_slice($post['hashtags'] ?? [], 0, 5),
            'visual_brief' => [
                'type' => 'vertical_video_9_16',
                'description' => 'Fast-paced talking head with b-roll transitions, bold yellow captions, and dynamic sound effects.',
                'audio_suggestion' => 'Trending subtle upbeat background track.',
            ],
            'thread_slides' => null,
            'character_count' => mb_strlen($script),
            'status' => 'ready',
        ];
    }

    /**
     * Format for Facebook (Conversational + Link friendly).
     */
    private function formatForFacebook(array $post): array
    {
        $body = "{$post['hook']}\n\n"
            . "{$post['caption']}\n\n"
            . "👇 {$post['cta']}\n\n"
            . implode(' ', array_slice($post['hashtags'] ?? [], 0, 3));

        return [
            'platform' => 'facebook',
            'format' => 'post',
            'hook' => $post['hook'],
            'body' => trim($body),
            'cta' => $post['cta'],
            'hashtags' => array_slice($post['hashtags'] ?? [], 0, 3),
            'visual_brief' => $post['visual_brief'] ?? null,
            'thread_slides' => null,
            'character_count' => mb_strlen($body),
            'status' => 'ready',
        ];
    }

    /**
     * Generic fallback formatting.
     */
    private function formatGeneric(array $post, string $platform): array
    {
        $body = "{$post['hook']}\n\n{$post['caption']}\n\n{$post['cta']}";

        return [
            'platform' => $platform,
            'format' => 'post',
            'hook' => $post['hook'],
            'body' => trim($body),
            'cta' => $post['cta'],
            'hashtags' => $post['hashtags'] ?? [],
            'visual_brief' => $post['visual_brief'] ?? null,
            'thread_slides' => null,
            'character_count' => mb_strlen($body),
            'status' => 'ready',
        ];
    }
}
