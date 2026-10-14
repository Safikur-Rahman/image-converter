<?php

namespace App\Services\Conversion;

use App\Enums\ConversionStatus;
use App\Models\Conversion;
use App\Models\ConversionFile;
use App\Models\SvgConversion;
use App\Services\BackgroundRemoval\BackgroundRemovalService;
use App\Services\Svg\SvgConversionService;
use App\Services\Usage\UsageTrackingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Coordinates the enterprise conversion pipeline inside queue jobs. Kept
 * independent of HTTP so both the web app and the API can reuse it.
 */
class ConversionProcessor
{
    public function __construct(
        protected SvgConversionService $svg,
        protected BackgroundRemovalService $backgroundRemoval,
        protected UsageTrackingService $usage,
        protected \App\Services\Storage\StorageService $storage,
        protected \App\Services\Image\ImagePreprocessingService $preprocess,
    ) {}

    /**
     * Run the full pipeline for a single conversion.
     */
    public function process(Conversion $conversion): void
    {
        if ($conversion->status === ConversionStatus::Cancelled) {
            return;
        }

        // Tracing a large photo easily outlasts hosting defaults such as
        // max_execution_time=60s. Grant the configured budget (also works for
        // sync jobs that run inside the HTTP request).
        set_time_limit((int) config('imageconverter.queue.conversion_timeout', 300) + 60);
        ini_set('memory_limit', (string) config('imageconverter.queue.conversion_memory_limit', '1024M'));

        $conversion->markStarted();

        $start = microtime(true);

        try {
            $sourcePath = $conversion->originalFile()?->path;
            if (!$sourcePath) {
                throw new \RuntimeException('Original file is missing from storage.');
            }

            $source = $this->storage->disk()->path($sourcePath);

            if ($conversion->remove_background) {
                $this->removeBackground($conversion, $source);
            }

            $this->vectorize($conversion, $source);

            $conversion->markCompleted();

            $this->usage->record($conversion->user, 'svg_conversion', 1, [
                'conversion_uuid' => $conversion->uuid,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);

            Log::info('Conversion completed', [
                'conversion_uuid' => $conversion->uuid,
                'user_id' => $conversion->user_id,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);

            $this->notifyUser($conversion, true);
        } catch (\Throwable $e) {
            $conversion->markFailed('Something went wrong while processing your image.');
            $conversion->svgConversion()?->update(['status' => 'failed']);

            Log::error('Conversion failed', [
                'conversion_uuid' => $conversion->uuid,
                'user_id' => $conversion->user_id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            \App\Models\SystemLog::create([
                'uuid' => (string) Str::uuid(),
                'level' => 'error',
                'source' => 'conversion',
                'message' => $e->getMessage(),
                'context' => ['conversion_uuid' => $conversion->uuid],
                'conversion_uuid' => $conversion->uuid,
                'user_id' => $conversion->user_id,
            ]);

            $this->notifyUser($conversion, false);

            throw $e;
        }
    }

    protected function removeBackground(Conversion $conversion, string $source): void
    {
        if (!$this->backgroundRemoval->isAvailable()) {
            throw new \RuntimeException(
                'Background removal was requested but no provider is configured.'
            );
        }

        $conversion->update(['status' => ConversionStatus::BackgroundRemoving]);

        $record = $conversion->backgroundRemoval()?->first()
            ?? $conversion->backgroundRemoval()->create([
                'uuid' => (string) Str::uuid(),
                'provider' => $this->backgroundRemoval->provider()?->name() ?? 'unknown',
                'status' => 'processing',
                'input_path' => $conversion->originalFile()?->path,
            ]);

        $outputAbsolute = $this->storage->disk()->path(
            'processed/'.$conversion->user_id.'/'.$conversion->uuid.'.png'
        );

        $this->backgroundRemoval->removeBackground($source, $outputAbsolute);

        $path = 'processed/'.$conversion->user_id.'/'.$conversion->uuid.'.png';
        $size = filesize($outputAbsolute);

        ConversionFile::updateOrCreate(
            ['conversion_id' => $conversion->id, 'type' => 'background_removed'],
            [
                'disk' => $this->storage->driver(),
                'path' => $path,
                'filename' => $conversion->uuid.'.png',
                'mime_type' => 'image/png',
                'size' => $size ?: 0,
            ]
        );

        $record->update(['status' => 'completed', 'output_path' => $path]);

        $this->usage->record($conversion->user, 'background_removal', 1, [
            'conversion_uuid' => $conversion->uuid,
            'provider' => $record->provider,
        ]);
    }

    protected function vectorize(Conversion $conversion, string $source): void
    {
        $conversion->update(['status' => ConversionStatus::Vectorizing]);

        $type = \App\Enums\ConversionType::from($conversion->conversion_type);
        $quality = \App\Enums\ConversionQuality::from($conversion->quality);

        // Ensure a PNG working copy (handles WebP and other GD-loadable inputs).
        $prepared = $this->preprocess->prepareForTracing($source, $conversion->user_id, $conversion->uuid);
        $workingPath = $prepared['png_path'];

        try {
            $result = $this->svg->convert($workingPath, $type, $quality, [
                'max_trace_dimension' => (int) config('imageconverter.max_trace_dimension', 2048),
            ]);
        } finally {
            if (is_file($workingPath)) {
                @unlink($workingPath);
            }
        }

        $svgPath = 'svg/'.$conversion->uuid.'.svg';
        $this->storage->disk()->put($svgPath, $result['svg']);

        ConversionFile::updateOrCreate(
            ['conversion_id' => $conversion->id, 'type' => 'svg'],
            [
                'disk' => $this->storage->driver(),
                'path' => $svgPath,
                'filename' => $conversion->uuid.'.svg',
                'mime_type' => 'image/svg+xml',
                'size' => $result['optimized_size'],
            ]
        );

        $record = $conversion->svgConversion()?->first() ?? new SvgConversion(['uuid' => (string) Str::uuid()]);

        $record->conversion_id = $conversion->id;
        $record->tracing_engine = $result['engine'];
        $record->status = 'completed';
        $record->input_width = $prepared['width'];
        $record->input_height = $prepared['height'];
        $record->output_width = $result['width'];
        $record->output_height = $result['height'];
        $record->original_svg_size = $result['original_size'];
        $record->optimized_svg_size = $result['optimized_size'];
        $record->path_count = $result['path_count'];
        $record->save();
    }

    protected function notifyUser(Conversion $conversion, bool $success): void
    {
        $user = $conversion->user;

        try {
            if ($success) {
                if ($user->notify_conversion_completed) {
                    $user->notify(new \App\Notifications\ConversionCompleted($conversion));
                }
            } else {
                if ($user->notify_conversion_failed) {
                    $user->notify(new \App\Notifications\ConversionFailed($conversion));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send conversion notification', [
                'conversion_uuid' => $conversion->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}