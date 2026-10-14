<?php

namespace App\Jobs;

use App\Models\Conversion;
use App\Services\Conversion\ConversionProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImageConversion implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public int $tries;

    public array $backoff = [5, 15, 60];

    public function __construct(public Conversion $conversion)
    {
        $this->timeout = (int) config('imageconverter.queue.conversion_timeout', 300);
        $this->tries = (int) config('imageconverter.queue.conversion_retries', 3);
    }

    public function handle(ConversionProcessor $processor): void
    {
        $processor->process($this->conversion);
    }

    public function failed(\Throwable $exception): void
    {
        // ConversionProcessor already marks the conversion failed and logs the
        // technical error. We only surface the retry count for observability.
        \Illuminate\Support\Facades\Log::warning('Conversion job exhausted retries', [
            'conversion_uuid' => $this->conversion->uuid,
            'error' => $exception->getMessage(),
        ]);
    }
}