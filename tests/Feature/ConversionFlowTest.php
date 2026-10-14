<?php

namespace Tests\Feature;

use App\Enums\ConversionStatus;
use App\Models\Conversion;
use App\Models\ConversionFile;
use App\Models\Plan;
use App\Models\User;
use App\Services\Storage\StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConversionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 12,
            'billing_interval' => 'monthly',
            'monthly_conversion_limit' => 200,
            'max_file_size_kb' => 10240,
            'max_dimensions' => 8000,
            'background_removal_enabled' => true,
            'api_enabled' => true,
            'is_active' => true,
        ]);
    }

    private function proUser(): User
    {
        return User::factory()->create(['plan_id' => $this->plan->id]);
    }

    public function test_guest_is_redirected_to_login_from_converter(): void
    {
        $this->get(route('converter'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_json_upload_is_rejected(): void
    {
        $this->postJson(route('converter.store'), [
            'image' => UploadedFile::fake()->image('x.png'),
            'conversion_type' => 'color',
            'quality' => 'high',
            'path_optimization' => 'balanced',
        ], ['Accept' => 'application/json'])->assertUnauthorized();
    }

    public function test_valid_upload_creates_conversion_and_redirects(): void
    {
        $user = $this->proUser();

        $response = $this->actingAs($user)->post(route('converter.store'), [
            'image' => UploadedFile::fake()->image('logo.png', 100, 80),
            'remove_background' => '0',
            'conversion_type' => 'color',
            'quality' => 'high',
            'path_optimization' => 'balanced',
        ]);

        $conversion = Conversion::where('user_id', $user->id)->first();

        $this->assertNotNull($conversion);
        $this->assertFalse((bool) $conversion->remove_background);
        $response->assertRedirect(route('conversions.show', $conversion->uuid));
        $response->assertSessionHas('conversion_started', true);
        $this->assertSame('logo.png', $conversion->original_filename);
        $this->assertSame(100, $conversion->width);
        $this->assertSame(80, $conversion->height);
        $this->assertInstanceOf(ConversionFile::class, $conversion->originalFile());

        // Queue runs synchronously in tests: the pipeline should have completed.
        $this->assertSame(ConversionStatus::Completed, $conversion->fresh()->status);
        $this->assertNotNull($conversion->fresh()->svgFile());
        $this->assertDatabaseHas('svg_conversions', ['conversion_id' => $conversion->id]);
    }

    public function test_ajax_upload_returns_json_with_uuid(): void
    {
        $user = $this->proUser();

        $response = $this->actingAs($user)
            ->post(route('converter.store'), [
                'image' => UploadedFile::fake()->image('ajax.png', 80, 60),
                'remove_background' => '0',
                'conversion_type' => 'color',
                'quality' => 'high',
                'path_optimization' => 'balanced',
            ], ['X-Requested-With' => 'XMLHttpRequest']);

        $conversion = Conversion::where('user_id', $user->id)->first();

        $this->assertNotNull($conversion);
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.uuid', $conversion->uuid);
        $this->assertSame('completed', $conversion->fresh()->status->value);
    }

    public function test_invalid_image_type_is_rejected(): void
    {
        $user = $this->proUser();

        $this->actingAs($user)->from(route('converter'))
            ->post(route('converter.store'), [
                'image' => UploadedFile::fake()->create('notes.txt', 100),
                'conversion_type' => 'color',
                'quality' => 'high',
                'path_optimization' => 'balanced',
            ])
            ->assertRedirect(route('converter'))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('conversions', 0);
    }

    public function test_corrupt_image_bytes_are_rejected_by_gd_verification(): void
    {
        $user = $this->proUser();
        $file = UploadedFile::fake()->create('fake.png', 120, 'image/png');
        // The stream is not a real PNG; GD must reject it.
        \Illuminate\Support\Facades\File::put($file->getPathname(), 'not a png just text bytes that exceed 0');

        $this->actingAs($user)->from(route('converter'))
            ->post(route('converter.store'), [
                'image' => $file,
                'conversion_type' => 'color',
                'quality' => 'high',
                'path_optimization' => 'balanced',
            ])
            ->assertRedirect(route('converter'))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('conversions', 0);
    }

    public function test_oversized_image_is_rejected(): void
    {
        $user = $this->proUser();

        config(['imageconverter.max_upload_size_kb' => 10]);

        $this->actingAs($user)->from(route('converter'))
            ->post(route('converter.store'), [
                'image' => UploadedFile::fake()->image('big.png')->size(2048),
                'conversion_type' => 'color',
                'quality' => 'high',
                'path_optimization' => 'balanced',
            ])
            ->assertRedirect(route('converter'))
            ->assertSessionHasErrors('image');
    }

    public function test_user_at_conversion_limit_cannot_upload(): void
    {
        $this->plan->update(['monthly_conversion_limit' => 1]);
        $user = $this->proUser();

        \App\Models\UsageRecord::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'action' => 'svg_conversion',
            'quantity' => 1,
            'period' => now()->format('Y-m'),
        ]);

        $this->actingAs($user)->from(route('converter'))
            ->post(route('converter.store'), [
                'image' => UploadedFile::fake()->image('ok.png', 50, 50),
                'conversion_type' => 'color',
                'quality' => 'high',
                'path_optimization' => 'balanced',
            ])
            ->assertRedirect(route('converter'))
            ->assertSessionHasErrors('limit');

        $this->assertDatabaseCount('conversions', 0);
    }

    public function test_status_endpoint_returns_current_status(): void
    {
        $user = $this->proUser();
        $conversion = Conversion::factory()->create([
            'user_id' => $user->id,
            'status' => ConversionStatus::Processing,
        ]);

        $this->actingAs($user)
            ->getJson(route('conversions.status', $conversion->uuid))
            ->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('success', true);
    }

    public function test_user_cannot_read_another_users_status(): void
    {
        $owner = $this->proUser();
        $intruder = $this->proUser();
        $conversion = Conversion::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->getJson(route('conversions.status', $conversion->uuid))
            ->assertForbidden();
    }

    public function test_owner_can_download_svg_and_download_is_tracked(): void
    {
        $user = $this->proUser();
        $conversion = Conversion::factory()->completed()->create(['user_id' => $user->id]);

        $meta = app(StorageService::class)->storeSvg('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"></svg>', $user->id, $conversion->uuid);
        ConversionFile::create([
            'conversion_id' => $conversion->id,
            'type' => 'svg',
            'disk' => $meta['disk'],
            'path' => $meta['path'],
            'filename' => $meta['filename'],
            'mime_type' => $meta['mime_type'],
            'size' => $meta['size'],
        ]);

        $this->actingAs($user)
            ->get(route('downloads.svg', $conversion->uuid))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml')
            ->assertDownload();

        $this->assertDatabaseCount('downloads', 1);
    }

    public function test_non_owner_cannot_download(): void
    {
        $owner = $this->proUser();
        $intruder = $this->proUser();
        $conversion = Conversion::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('downloads.svg', $conversion->uuid))
            ->assertForbidden();
    }

    public function test_download_is_404_when_file_missing(): void
    {
        $user = $this->proUser();
        $conversion = Conversion::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('downloads.svg', $conversion->uuid))
            ->assertNotFound();
    }

    public function test_show_page_embeds_svg_via_inline_preview_url(): void
    {
        $user = $this->proUser();
        $conversion = Conversion::factory()->completed()->create(['user_id' => $user->id]);

        $meta = app(StorageService::class)->storeSvg('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10" fill="#fff"/></svg>', $user->id, $conversion->uuid);
        ConversionFile::create([
            'conversion_id' => $conversion->id,
            'type' => 'svg',
            'disk' => $meta['disk'],
            'path' => $meta['path'],
            'filename' => $meta['filename'],
            'mime_type' => $meta['mime_type'],
            'size' => $meta['size'],
        ]);

        $original = UploadedFile::fake()->image('big.png', 3000, 1500);
        $origMeta = app(StorageService::class)->storeOriginal($original, $user->id, $conversion->uuid);
        ConversionFile::create([
            'conversion_id' => $conversion->id,
            'type' => 'original',
            'disk' => $origMeta['disk'],
            'path' => $origMeta['path'],
            'filename' => $origMeta['filename'],
            'mime_type' => $origMeta['mime_type'],
            'size' => $origMeta['size'],
        ]);

        $page = $this->actingAs($user)
            ->get(route('conversions.show', $conversion->uuid))
            ->assertOk();

        $page->assertSee(route('downloads.preview-svg', $conversion->uuid));
        $page->assertSee(route('downloads.preview-original', $conversion->uuid));
        $page->assertDontSee('data:image/svg+xml;base64');

        $this->actingAs($user)
            ->get(route('downloads.preview-svg', $conversion->uuid))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml');

        $this->actingAs($user)
            ->get(route('downloads.preview-original', $conversion->uuid))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_non_owner_cannot_preview_svg(): void
    {
        $owner = $this->proUser();
        $intruder = $this->proUser();
        $conversion = Conversion::factory()->completed()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('downloads.preview-svg', $conversion->uuid))
            ->assertForbidden();
    }

    public function test_tracer_downscales_large_input_without_losing_geometry(): void
    {
        // Build a large image that forces the tracer's internal downscale path.
        $tmp = tempnam(sys_get_temp_dir(), 'trace-').'.png';
        $img = imagecreatetruecolor(1600, 1000);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imagefilledrectangle($img, 50, 50, 1550, 450, imagecolorallocate($img, 12, 34, 56));
        imagefilledrectangle($img, 50, 550, 1550, 950, imagecolorallocate($img, 200, 45, 45));
        imagepng($img, $tmp);
        imagedestroy($img);

        try {
            $result = app(\App\Services\Svg\SvgConversionService::class)->convert(
                $tmp,
                \App\Enums\ConversionType::Color,
                \App\Enums\ConversionQuality::Medium,
                ['max_trace_dimension' => 200]
            );

            // Downscaled to the cap and both colour blocks survive.
            $this->assertSame(200, $result['width']);
            $this->assertSame(125, $result['height']);
            $this->assertGreaterThanOrEqual(2, $result['path_count']);
            $this->assertStringContainsString('<svg', $result['svg']);
        } finally {
            @unlink($tmp);
        }
    }
}