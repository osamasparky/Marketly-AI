<?php

namespace App\Support\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FileUploadGuard
{
    private const MAX_DOCUMENT_SIZE_BYTES = 25 * 1024 * 1024; // 25 MB
    private const MAX_IMAGE_SIZE_BYTES = 10 * 1024 * 1024;    // 10 MB

    private const ALLOWED_MIME_TYPES = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'text/plain' => 'txt',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const FORBIDDEN_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd',
        'js', 'vbs', 'jar', 'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 'shtml',
    ];

    /**
     * Validate an uploaded file for security, format, size, and MIME integrity.
     *
     * @param UploadedFile $file
     * @param string $category ('document' or 'image')
     * @return array{safe_filename: string, mime_type: string, extension: string, size_bytes: int}
     */
    public static function validate(UploadedFile $file, string $category = 'document'): array
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('Corrupted or invalid file upload.');
        }

        $size = $file->getSize();
        $maxSize = ($category === 'image') ? self::MAX_IMAGE_SIZE_BYTES : self::MAX_DOCUMENT_SIZE_BYTES;

        if ($size > $maxSize) {
            $maxMb = $maxSize / (1024 * 1024);
            throw new InvalidArgumentException("File size exceeds maximum allowed limit of {$maxMb}MB.");
        }

        $clientExt = strtolower($file->getClientOriginalExtension());
        if (in_array($clientExt, self::FORBIDDEN_EXTENSIONS, true)) {
            throw new InvalidArgumentException("Forbidden file extension '{$clientExt}' detected.");
        }

        // Validate true binary MIME type using PHP fileinfo
        $realMime = $file->getMimeType();
        if (!array_key_exists($realMime, self::ALLOWED_MIME_TYPES)) {
            throw new InvalidArgumentException("Unsupported or untrusted file MIME type: '{$realMime}'.");
        }

        $safeExtension = self::ALLOWED_MIME_TYPES[$realMime];

        // Deep inspect SVG or text files for embedded script tags
        if ($realMime === 'image/svg+xml' || $safeExtension === 'svg') {
            self::assertSvgIsSafe($file->getRealPath());
        }

        // Generate unguessable sanitized storage filename
        $safeFilename = Str::uuid()->toString() . '.' . $safeExtension;

        return [
            'safe_filename' => $safeFilename,
            'mime_type' => $realMime,
            'extension' => $safeExtension,
            'size_bytes' => $size,
        ];
    }

    /**
     * Inspect SVG content for malicious embedded scripts, handlers, or foreign objects.
     */
    private static function assertSvgIsSafe(string $path): void
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new InvalidArgumentException('Unable to read SVG for security analysis.');
        }

        $dangerousPatterns = [
            '/<script/i',
            '/onload=/i',
            '/onerror=/i',
            '/onclick=/i',
            '/onmouseover=/i',
            '/javascript:/i',
            '/<foreignObject/i',
            '/<iframe/i',
            '/<embed/i',
            '/<object/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new InvalidArgumentException('SVG file contains unsafe executable elements or event handlers.');
            }
        }
    }
}
