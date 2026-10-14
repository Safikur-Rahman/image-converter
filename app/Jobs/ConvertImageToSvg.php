<?php

namespace App\Jobs;

use App\Models\Conversion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Granular pipeline stage: raster-to-vector tracing. Delegates to the
 * SvgConversionService which coordinates tracing + validation + optimization.
 */
class ConvertImageToSvg implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public int $tries = 3;

    public array $backoff = [5, 15, 60];

    public function __construct(public Conversion $conversion)
    {
        $this->timeout = (int) config('imageconverter.queue.conversion_timeout', 300);
    }

    public function handle(): void
    {
        $conversion = $this->conversion;

        $svgService = app(\App\Services\Svg\SvgConversionService::class);
        $storage = app(\App\Services\Storage\StorageService::class);
        $preprocess = app(\App\Services\Image\ImagePreprocessingService::class);

        $source = match (true) {
            $conversion->file('background_removed') !== null => $conversion->file('background_removed')?->path,
            default => $conversion->originalFile()?->path,
        };

        if (!$source) {
            throw new \RuntimeException('No source image available for SVG conversion.');
        }

        $prepared = $preprocess->prepareForTracing(
            $storage->disk()->path($source),
            $conversion->user_id,
            $conversion->uuid
        );

        try {
            $result = $svgService->convert(
                $prepared['png_path'],
                \App\Enums\ConversionType::from($conversion->conversion_type),
                \App\Enums\ConversionQuality::from($conversion->quality),
                ['max_trace_dimension' => (int) config('imageconverter.max_trace_dimension', 2048)]
            );
        } finally {
            @unlink($prepared['png_path']);
        }

        $svgPath = 'svg/'.$conversion->uuid.'.svg';
        $storage->disk()->put($svgPath, $result['svg']);

        \App\Models\ConversionFile::updateOrCreate(
            ['conversion_id' => $conversion->id, 'type' => 'svg'],
            [
                'disk' => $storage->driver(),
                'path' => $svgPath,
                'filename' => $conversion->uuid.'.svg',
                'mime_type' => 'image/svg+xml',
                'size' => $result['optimized_size'],
            ]
        );
    }
}