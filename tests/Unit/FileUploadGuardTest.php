<?php

namespace Tests\Unit;

use App\Support\Security\FileUploadGuard;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Tests\TestCase;

class FileUploadGuardTest extends TestCase
{
    public function test_valid_pdf_upload(): void
    {
        $file = UploadedFile::fake()->create('company_profile.pdf', 1024, 'application/pdf');
        $result = FileUploadGuard::validate($file, 'document');

        $this->assertEquals('pdf', $result['extension']);
        $this->assertEquals('application/pdf', $result['mime_type']);
        $this->assertStringEndsWith('.pdf', $result['safe_filename']);
    }

    public function test_rejects_executable_php_file(): void
    {
        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $this->expectException(InvalidArgumentException::class);
        FileUploadGuard::validate($file, 'document');
    }

    public function test_rejects_oversized_file(): void
    {
        // Max image size is 10MB; create 15MB file
        $file = UploadedFile::fake()->create('huge_banner.jpg', 15 * 1024, 'image/jpeg');

        $this->expectException(InvalidArgumentException::class);
        FileUploadGuard::validate($file, 'image');
    }
}
