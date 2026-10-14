<?php

namespace App\Jobs;

use App\Models\Conversion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Granular pipeline stage: final SVG optimization (idempotent re-optimization
 * with validation). Safe to retry — it only rewrites an existing file.
 */
class OptimizeSvg implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public Conversion $conversion) {}

    public function handle(): void
    {
        $file = $this->conversion->svgFile();
        if (!$file) {
            throw new \RuntimeException('No SVG file to optimize.');
        }

        $storage = app(\App\Services\Storage\StorageService::class);
        $optimizer = app(\App\Services\Svg\SvgOptimizationService::class);
        $validator = app(\App\Services\Svg\SvgValidationService::class);

        $content = $storage->disk()->get($file->path);
        if ($content === null) {
            throw new \RuntimeException('SVG file could not be read for optimization.');
        }

        $optimized = $optimizer->optimize($content);

        $valid = $validator->validate($optimized);
        if (!$valid['valid']) {
            throw new \RuntimeException('Optimization produced invalid SVG: '.implode(' ', $valid['errors']));
        }

        $storage->disk()->put($file->path, $optimized);

        $file->update(['size' => strlen($optimized)]);
    }
}