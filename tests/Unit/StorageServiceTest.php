<?php

namespace Tests\Unit;

use App\Models\Conversion;
use App\Models\ConversionFile;
use App\Services\Storage\StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private StorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->storage = new StorageService;
    }

    public function test_it_stores_a_original_under_user_directory(): void
    {
        $file = UploadedFile::fake()->image('photo.png', 40, 40);

        $meta = $this->storage->storeOriginal($file, 7, 'abc-123');

        $this->assertSame('originals/7/abc-123.png', $meta['path']);
        $this->assertSame('photo.png', $meta['filename']);
        $this->assertSame('image/png', $meta['mime_type']);
        Storage::disk('local')->assertExists($meta['path']);
    }

    public function test_it_stores_svg_content(): void
    {
        $meta = $this->storage->storeSvg('<svg xmlns="https://www.w3.org/2000/svg"></svg>', 3, 'xyz');

        $this->assertSame('svg/xyz.svg', $meta['path']);
        $this->assertSame('image/svg+xml', $meta['mime_type']);
        Storage::disk('local')->assertExists($meta['path']);
        $this->assertSame($meta['size'], strlen('<svg xmlns="https://www.w3.org/2000/svg"></svg>'));
    }

    public function test_delete_all_for_conversion_removes_files(): void
    {
        $user = \App\Models\User::factory()->create();
        $conversion = Conversion::factory()->create(['user_id' => $user->id, 'uuid' => 'conv-1']);

        $stored = $this->storage->storeProcessed('data', 1, 'conv-1');
        $original = $this->storage->storeSvg('data', 1, 'conv-1');

        ConversionFile::create([
            'conversion_id' => $conversion->id,
            'type' => 'processed',
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'filename' => $stored['filename'],
            'mime_type' => $stored['mime_type'],
            'size' => $stored['size'],
        ]);
        ConversionFile::create([
            'conversion_id' => $conversion->id,
            'type' => 'svg',
            'disk' => $original['disk'],
            'path' => $original['path'],
            'filename' => $original['filename'],
            'mime_type' => $original['mime_type'],
            'size' => $original['size'],
        ]);

        Storage::disk('local')->assertExists($stored['path']);

        $this->storage->deleteAllForConversion($conversion);

        Storage::disk('local')->assertMissing($stored['path']);
        Storage::disk('local')->assertMissing($original['path']);
        $this->assertDatabaseCount('conversion_files', 0);
    }

    public function test_secure_download_name_slugs_filenames(): void
    {
        $this->assertSame('map-marker-v2', StorageService::secureDownloadName('Map Marker (v2)!'));
        $this->assertSame('image', StorageService::secureDownloadName('!!!'));
    }

    public function test_read_round_trips_content(): void
    {
        $meta = $this->storage->storeSvg('<svg></svg>', 1, 'read-test');

        $this->assertSame('<svg></svg>', $this->storage->read($meta['path']));
    }
}