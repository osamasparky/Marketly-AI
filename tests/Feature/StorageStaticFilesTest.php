<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageStaticFilesTest extends TestCase
{
    public function test_storage_route_serves_uploaded_and_generated_media_files(): void
    {
        Storage::disk('public')->put('creative-assets/test/sample.png', 'FAKECONTENT_PNG_IMAGE_BYTES');

        $response = $this->get('/storage/creative-assets/test/sample.png');

        $response->assertStatus(200);
        $this->assertEquals('FAKECONTENT_PNG_IMAGE_BYTES', file_get_contents($response->getFile()->getPathname()));
    }

    public function test_storage_route_returns_404_for_non_existent_file(): void
    {
        $response = $this->get('/storage/non-existent-file.png');

        $response->assertStatus(404);
    }
}
