<?php

namespace App\Services\Svg\Engines;

use App\Enums\ConversionType;
use App\Enums\ConversionQuality;

interface TracingEngineInterface
{
    /**
     * Trace a raster image into SVG markup.
     *
     * @param  string  $imagePath  Absolute path to the source raster image.
     * @param  ConversionType  $type  Color or black & white.
     * @param  ConversionQuality  $quality  Detail level.
     *
     * @return array{
     *     svg: string,
     *     width: int,
     *     height: int,
     *     path_count: int
     * }
     */
    public function trace(string $imagePath, ConversionType $type, ConversionQuality $quality, array $options = []): array;
}