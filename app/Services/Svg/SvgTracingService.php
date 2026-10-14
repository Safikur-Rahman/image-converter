<?php

namespace App\Services\Svg;

use App\Enums\ConversionQuality;
use App\Enums\ConversionType;
use App\Services\Svg\Engines\PurePhpTracingEngine;
use App\Services\Svg\Engines\TracingEngineInterface;

/**
 * Dispatches raster-to-vector tracing to the configured engine.
 *
 * The controller / pipeline only ever talks to this service, so the tracing
 * implementation can be swapped (e.g. a Potrace bindings engine, an external
 * service, or a future AI-assisted tracer) without touching application logic.
 */
class SvgTracingService
{
    private TracingEngineInterface $engine;

    public function __construct(TracingEngineInterface $engine = null)
    {
        $this->engine = $engine ?? $this->resolveEngine();
    }

    public function trace(string $imagePath, ConversionType $type, ConversionQuality $quality, array $options = []): array
    {
        return $this->engine->trace($imagePath, $type, $quality, array_merge([
            'max_trace_dimension' => (int) config('imageconverter.max_trace_dimension', 2048),
        ], $options));
    }

    public function engineName(): string
    {
        return match (get_class($this->engine)) {
            PurePhpTracingEngine::class => 'pure_php',
            default => class_basename($this->engine),
        };
    }

    private function resolveEngine(): TracingEngineInterface
    {
        $configured = (string) config('imageconverter.tracing_engine', 'pure_php');

        return match ($configured) {
            'pure_php' => new PurePhpTracingEngine,
            default => new PurePhpTracingEngine,
        };
    }
}