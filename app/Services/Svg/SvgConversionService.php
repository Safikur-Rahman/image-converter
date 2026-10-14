<?php

namespace App\Services\Svg;

use App\Enums\ConversionQuality;
use App\Enums\ConversionType;

/**
 * High level entry point that turns a raster file into a validated,
 * optimized SVG. The conversion pipeline delegates to this service; it in turn
 * coordinates tracing, validation and optimization without coupling callers to
 * any specific tracing engine.
 */
class SvgConversionService
{
    public function __construct(
        protected SvgTracingService $tracing,
        protected SvgValidationService $validation,
        protected SvgOptimizationService $optimization,
    ) {}

    /**
     * @return array{
     *     svg: string,
     *     engine: string,
     *     width: int,
     *     height: int,
     *     path_count: int,
     *     original_size: int,
     *     optimized_size: int
     * }
     */
    public function convert(string $imagePath, ConversionType $type, ConversionQuality $quality, array $options = []): array
    {
        $result = $this->tracing->trace($imagePath, $type, $quality, $options);

        $valid = $this->validation->validate($result['svg']);
        if (!$valid['valid']) {
            throw new \RuntimeException(
                'Tracing produced an invalid SVG: '.implode(' ', $valid['errors'])
            );
        }

        $optimized = $this->optimization->optimize($result['svg']);

        $optimizedValid = $this->validation->validate($optimized);
        if (!$optimizedValid['valid']) {
            // Never hand back something we know is broken.
            throw new \RuntimeException(
                'SVG optimization produced an invalid document: '.implode(' ', $optimizedValid['errors'])
            );
        }

        return [
            'svg' => $optimized,
            'engine' => $this->tracing->engineName(),
            'width' => $result['width'],
            'height' => $result['height'],
            'path_count' => $result['path_count'],
            'original_size' => strlen($result['svg']),
            'optimized_size' => strlen($optimized),
        ];
    }
}