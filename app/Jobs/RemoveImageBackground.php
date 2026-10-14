<?php

namespace App\Jobs;

use App\Models\Conversion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Granular pipeline stage. Dispatched by the pipeline when background removal
 * is requested; delegates to the provider-agnostic service.
 */
class RemoveImageBackground implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 3;

    public array $backoff = [5, 15, 60];

    public function __construct(public Conversion $conversion) {}

    public function handle(\App\Services\BackgroundRemoval\BackgroundRemovalService $service): void
    {
        $conversion = $this->conversion;

        $source = $conversion->originalFile()?->path;
        if (!$source) {
            throw new \RuntimeException('Original file is missing for background removal.');
        }

        $storage = app(\App\Services\Storage\StorageService::class);
        $absoluteSource = $storage->disk()->path($source);

        $outputPath = 'processed/'.$conversion->user_id.'/'.$conversion->uuid.'.png';
        $absoluteOutput = $storage->disk()->path($outputPath);

        $service->removeBackground($absoluteSource, $absoluteOutput);

        \App\Models\ConversionFile::updateOrCreate(
            ['conversion_id' => $conversion->id, 'type' => 'background_removed'],
            [
                'disk' => $storage->driver(),
                'path' => $outputPath,
                'filename' => $conversion->uuid.'.png',
                'mime_type' => 'image/png',
                'size' => filesize($absoluteOutput) ?: 0,
            ]
        );
    }
}