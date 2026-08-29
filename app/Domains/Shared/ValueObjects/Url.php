<?php

namespace App\Domains\Shared\ValueObjects;

use InvalidArgumentException;

class Url
{
    private readonly string $value;
    private readonly string $host;
    private readonly string $scheme;

    public function __construct(string $value, bool $allowPrivateIps = false)
    {
        $sanitized = filter_var(trim($value), FILTER_VALIDATE_URL);

        if ($sanitized === false) {
            throw new InvalidArgumentException("Invalid URL format: '{$value}'");
        }

        $parts = parse_url($sanitized);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("URL scheme must be http or https, got '{$scheme}'");
        }

        if (!$allowPrivateIps && $this->isPrivateOrRestrictedHost($host)) {
            throw new InvalidArgumentException("Target host '{$host}' is restricted or points to a private network (SSRF Protection)");
        }

        $this->value = $sanitized;
        $this->scheme = $scheme;
        $this->host = $host;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function scheme(): string
    {
        return $this->scheme;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Checks if a hostname or IP address is private/loopback/cloud metadata.
     */
    private function isPrivateOrRestrictedHost(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return true;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : @gethostbyname($host);

        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            // Block private and reserved IP ranges (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 127.0.0.0/8, 169.254.0.0/16)
            $isPrivate = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) === false;

            if ($isPrivate || $ip === '169.254.169.254') {
                return true;
            }
        }

        return false;
    }
}
