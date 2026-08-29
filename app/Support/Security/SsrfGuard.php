<?php

namespace App\Support\Security;

use InvalidArgumentException;

class SsrfGuard
{
    private const BLOCKED_HOSTS = [
        'localhost',
        'metadata.google.internal',
        'metadata.gcp.internal',
        'instance-data',
    ];

    private const BLOCKED_IPS = [
        '169.254.169.254', // AWS / GCP / Azure metadata endpoint
        '127.0.0.1',
        '0.0.0.0',
        '::1',
    ];

    /**
     * Validate a URL against SSRF vulnerabilities.
     *
     * @param string $url
     * @param bool $allowPrivate (strictly false for untrusted inputs)
     * @return string Validated sanitized URL
     * @throws InvalidArgumentException
     */
    public static function validateUrl(string $url, bool $allowPrivate = false): string
    {
        $sanitized = filter_var(trim($url), FILTER_VALIDATE_URL);

        if ($sanitized === false) {
            throw new InvalidArgumentException("Invalid URL format provided: '{$url}'");
        }

        $parts = parse_url($sanitized);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("Forbidden URL scheme '{$scheme}'. Only http and https are allowed.");
        }

        if (!$allowPrivate) {
            self::assertHostIsPublic($host, $port);
        }

        return $sanitized;
    }

    /**
     * Assert that host and resolved IPs are strictly public and non-internal.
     */
    private static function assertHostIsPublic(string $host, int $port): void
    {
        if (in_array($host, self::BLOCKED_HOSTS, true)) {
            throw new InvalidArgumentException("Target host '{$host}' is restricted.");
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.internal') || str_ends_with($host, '.lan')) {
            throw new InvalidArgumentException("Internal domain targets are forbidden (SSRF Protection).");
        }

        // Resolve DNS
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : @dns_get_record($host, DNS_A + DNS_AAAA);
        $resolvedIps = [];

        if (is_array($ips)) {
            foreach ($ips as $record) {
                if (is_array($record) && isset($record['ip'])) {
                    $resolvedIps[] = $record['ip'];
                } elseif (is_array($record) && isset($record['ipv6'])) {
                    $resolvedIps[] = $record['ipv6'];
                }
            }
        }

        if (empty($resolvedIps)) {
            $fallbackIp = @gethostbyname($host);
            if ($fallbackIp && $fallbackIp !== $host) {
                $resolvedIps[] = $fallbackIp;
            } elseif (filter_var($host, FILTER_VALIDATE_IP)) {
                $resolvedIps[] = $host;
            }
        }

        foreach ($resolvedIps as $ip) {
            self::assertIpIsPublic($ip);
        }
    }

    /**
     * Assert that an IP is not private, loopback, link-local, or cloud metadata.
     */
    private static function assertIpIsPublic(string $ip): void
    {
        if (in_array($ip, self::BLOCKED_IPS, true)) {
            throw new InvalidArgumentException("Access to target IP '{$ip}' is restricted.");
        }

        // Check for IPv4 private & reserved ranges
        $isPublicV4 = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        // Check for IPv6 private & reserved ranges
        $isPublicV6 = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if (!$isPublicV4 && !$isPublicV6) {
            throw new InvalidArgumentException("IP address '{$ip}' belongs to a private, reserved, or loopback network (SSRF Protection).");
        }
    }
}
