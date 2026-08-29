<?php

namespace Tests\Unit;

use App\Support\Security\SsrfGuard;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SsrfGuardTest extends TestCase
{
    public function test_valid_public_url_passes(): void
    {
        $url = 'https://google.com/search?q=marketly';
        $validated = SsrfGuard::validateUrl($url);
        $this->assertEquals($url, $validated);
    }

    public function test_blocks_localhost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SsrfGuard::validateUrl('http://localhost/admin');
    }

    public function test_blocks_loopback_ip(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SsrfGuard::validateUrl('http://127.0.0.1:8000/api');
    }

    public function test_blocks_cloud_metadata_ip(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SsrfGuard::validateUrl('http://169.254.169.254/latest/meta-data/');
    }

    public function test_blocks_private_subnet_10(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SsrfGuard::validateUrl('http://10.0.0.5/internal');
    }

    public function test_blocks_private_subnet_192(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SsrfGuard::validateUrl('http://192.168.1.1/router');
    }

    public function test_blocks_internal_domains(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SsrfGuard::validateUrl('http://database.internal:5432');
    }

    public function test_blocks_file_scheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SsrfGuard::validateUrl('file:///etc/passwd');
    }
}
