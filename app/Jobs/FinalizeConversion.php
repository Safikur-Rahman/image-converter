<?php

namespace App\Jobs;

use App\Enums\ConversionStatus;
use App\Models\Conversion;
use App\Services\Usage\UsageTrackingService;
use App\Services\Svg\SvgValidationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Final pipeline stage: verify the produced SVG one last time, mark the
 * conversion completed (or failed) and emit notifications. Idempotent.
 */
class FinalizeConversion implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 2;

    public function __construct(public Conversion $conversion) {}

    public function handle(): void
    {
        $validator = app(SvgValidationService::class);
        $usage = app(UsageTrackingService::class);

        $file = $this->conversion->svgFile();

        if (!$file) {
            throw new \RuntimeException('Finalize failed: no SVG output exists.');
        }

        $content = app(\App\Services\Storage\StorageService::class)->disk()->get($file->path);
        $valid = $validator->validate($content ?? '');

        if (!$valid['valid']) {
            $this->conversion->update(['status' => ConversionStatus::Failed]);

            throw new \RuntimeException('Final SVG validation failed: '.implode(' ', $valid['errors']));
        }

        $this->conversion->markCompleted();

        $usage->record($this->conversion->user, 'svg_conversion', 1, [
            'conversion_uuid' => $this->conversion->uuid,
        ]);

        $user = $this->conversion->user;
        if ($user->notify_conversion_completed) {
            $user->notify(new \App\Notifications\ConversionCompleted($this->conversion));
        }
    }
}