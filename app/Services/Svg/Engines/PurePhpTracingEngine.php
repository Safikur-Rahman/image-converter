<?php

namespace App\Services\Svg\Engines;

use App\Enums\ConversionQuality;
use App\Enums\ConversionType;

/**
 * Pure-PHP raster-to-vector tracer.
 *
 * The implementation performs genuine vectorization:
 *
 *  1. The raster is loaded with GD and downscaled to a sane trace dimension.
 *  2. Pixels are assigned to a small palette of dominant colors (color mode)
 *     or to a luminance threshold (black & white mode). Transparent pixels
 *     become "out" so they never generate geometry.
 *  3. For every bucket the pixels that belong to it are expanded into unit
 *     squares. Shared edges between neighbouring squares are removed, leaving
 *     closed boundary loops (classic "merge squares" contouring on a pixel
 *     grid).
 *  4. Loops are chained into vertex rings and simplified with a Douglas-Peucker
 *     style line reduction so the resulting SVG is compact instead of a pixel
 *     explosion.
 *  5. A valid SVG document with a viewBox and root element is produced.
 *
 * The engine intentionally avoids embedding the raster inside the SVG.
 */
class PurePhpTracingEngine implements TracingEngineInterface
{
    public function trace(string $imagePath, ConversionType $type, ConversionQuality $quality, array $options = []): array
    {
        $image = @imagecreatefromstring((string) file_get_contents($imagePath));

        if ($image === false) {
            throw new \RuntimeException('Unable to decode the source image.');
        }

        try {
            $image = $this->prepareCanvas($image, $options);

            [$width, $height] = [imagesx($image), imagesy($image)];

            $this->blurImage($image, $quality->blurPasses());

            $alphaThreshold = (int) ($options['alpha_threshold'] ?? 110);
            $luminanceThreshold = (int) ($options['luminance_threshold'] ?? 190);
            $colors = $this->readPixels($image, $width, $height, $alphaThreshold, $type);

            if ($type === ConversionType::BlackWhite) {
                $masks = [[
                    'color' => [0, 0, 0],
                    'mask' => $this->buildBinaryMask($colors, $width, $height, fn (int $px) => $this->pxLuminance($px) < $luminanceThreshold),
                ]];
            } else {
                $palette = $this->extractPalette($colors, $quality->colorCount());
                $masks = $this->buildPaletteMasks($colors, $width, $height, $palette);
            }

            unset($colors);

            $tolerance = match ($quality) {
                ConversionQuality::Low => 1.8,
                ConversionQuality::Medium => 1.1,
                ConversionQuality::High => 0.5,
            };

            $svgParts = [];
            $pathCount = 0;

            foreach ($masks as $bucket) {
                $loops = $this->traceBoundaries($bucket['mask'], $width, $height);

                $subpaths = [];
                $counted = 0;

                foreach ($loops as $loop) {
                    // Despeckle: skip noise fragments that are smaller than a
                    // 2x2 pixel square before spending time simplifying them.
                    if (count($loop) < 6) {
                        continue;
                    }

                    $simplified = $this->simplifyLoop($loop, $tolerance);

                    if (count($simplified) < 3) {
                        continue;
                    }

                    $subpaths[] = $this->loopToPathData($simplified);
                    $counted++;
                }

                if ($counted === 0) {
                    continue;
                }

                // All loops for a bucket share one path element so that
                // fill-rule="evenodd" genuinely punches holes for nested rings.
                $svgParts[] = sprintf(
                    '<path fill="rgb(%d,%d,%d)" fill-rule="evenodd" d="%s"/>',
                    $bucket['color'][0],
                    $bucket['color'][1],
                    $bucket['color'][2],
                    implode('', $subpaths)
                );

                $pathCount += $counted;
            }

            if ($pathCount === 0) {
                $svgParts[] = '<rect x="0" y="0" width="'.$width.'" height="'.$height.'" fill="#ffffff"/>';
                $pathCount = 1;
            }

            $svg = '<svg xmlns="http://www.w3.org/2000/svg"'
                .' width="'.$width.'" height="'.$height.'"'
                .' viewBox="0 0 '.$width.' '.$height.'"'
                .'>'
                .implode('', $svgParts)
                .'</svg>';

            return [
                'svg' => $svg,
                'width' => $width,
                'height' => $height,
                'path_count' => $pathCount,
            ];
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * Downscale huge images so the trace stays fast and memory friendly.
     * Returns the GD image to continue processing (the input is destroyed
     * when a resized copy is made).
     */
    protected function prepareCanvas(\GdImage $image, array $options): \GdImage
    {
        $maxDim = (int) ($options['max_trace_dimension'] ?? config('imageconverter.max_trace_dimension', 1600));
        $width = imagesx($image);
        $height = imagesy($image);

        $largest = max($width, $height);
        if ($largest <= $maxDim) {
            return $image;
        }

        $scale = $maxDim / $largest;
        $newWidth = (int) round($width * $scale);
        $newHeight = (int) round($height * $scale);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        imagedestroy($image);

        return $resized;
    }
/**
     * Smooth the canvas so JPEG noise doesn't fragment mask regions into
     * thousands of tiny speckle loops. More passes = flatter, fewer
     * silhouettes; fewer passes = sharper edges.
     *
     * GD's imageconvolution is unreliable across decode paths (a PNG may not
     * blur identically to the same pixels loaded as JPEG), so a standard
     * gaussian filter is used instead.
     */
    protected function blurImage(\GdImage $image, int $passes): void
    {
        $passes = max(0, min(6, $passes));

        for ($pass = 0; $pass < $passes; $pass++) {
            imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        }
    }

    /**
     * Read every pixel into a compact row-based representation.
     *
     * Each pixel is stored as a single 32-bit integer:
     *   bit 31            : opaque flag (1 = opaque)
     *   bits 23..16       : green
     *   bits 15..8        : red
     *   bits 7..0         : blue
     *
     * A packed scalar instead of a per-pixel associative array keeps photos
     * (millions of pixels) memory-friendly during tracing.
     *
     * @return array<int, array<int, int>>
     */
    protected function readPixels(\GdImage $image, int $width, int $height, int $alphaThreshold, ConversionType $type): array
    {
        $rows = [];

        for ($y = 0; $y < $height; $y++) {
            $row = [];
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $a = ($rgb >> 24) & 0x7F;
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $opaque = $a <= $alphaThreshold;

                $row[] = (($opaque ? 1 : 0) << 31) | ($g << 16) | ($r << 8) | $b;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    protected function pxOpaque(int $px): bool
    {
        return ($px >> 31) === 1;
    }

    protected function pxRed(int $px): int
    {
        return ($px >> 8) & 0xFF;
    }

    protected function pxGreen(int $px): int
    {
        return ($px >> 16) & 0xFF;
    }

    protected function pxBlue(int $px): int
    {
        return $px & 0xFF;
    }

    protected function pxLuminance(int $px): int
    {
        return (int) round(
            0.299 * $this->pxRed($px)
            + 0.587 * $this->pxGreen($px)
            + 0.114 * $this->pxBlue($px)
        );
    }

    /**
     * @param  callable(int): bool  $predicate
     * @return string[] Mask rows of '1'/'0'
     */
    protected function buildBinaryMask(array $colors, int $width, int $height, callable $predicate): array
    {
        $rows = [];
        for ($y = 0; $y < $height; $y++) {
            $line = '';
            for ($x = 0; $x < $width; $x++) {
                $line .= $predicate($colors[$y][$x]) ? '1' : '0';
            }
            $rows[] = $line;
        }

        return $rows;
    }

    /**
     * Extract a reduced palette of dominant opaque colors.
     *
     * @param  array<int, array<int, int>>  $colors
     * @return array<int, array{r: int, g: int, b: int}>
     */
    protected function extractPalette(array $colors, int $limit): array
    {
        $histogram = [];

        foreach ($colors as $row) {
            foreach ($row as $px) {
                if (!$this->pxOpaque($px)) {
                    continue;
                }
                $key = (($this->pxRed($px) >> 3) << 10) | (($this->pxGreen($px) >> 3) << 5) | ($this->pxBlue($px) >> 3);
                $histogram[$key] = ($histogram[$key] ?? 0) + 1;
            }
        }

        if ($histogram === []) {
            return [[255, 255, 255]];
        }

        arsort($histogram);

        $limit = max(2, min($limit, 128));
        $entries = array_keys(array_slice($histogram, 0, $limit, true));

        $palette = [];
        foreach ($entries as $key) {
            $palette[] = [
                (($key >> 10) & 0x1F) << 3 | 4,

                (($key >> 5) & 0x1F) << 3 | 4,
                ($key & 0x1F) << 3 | 4,
            ];
        }

        return $palette;
    }

    /**
     * Assign every opaque pixel to its nearest palette colour.
     *
     * A 5-bit-per-channel lookup table (32768 entries) precomputes the nearest
     * palette index once, so assigning millions of photo pixels is O(1) each
     * instead of one Euclidean distance scan over the whole palette per pixel.
     *
     * @return array<int, array{color: array<int>, mask: string[]}>
     */
    protected function buildPaletteMasks(array $colors, int $width, int $height, array $palette): array
    {
        $lut = $this->nearestPaletteLookup($palette);

        $buckets = array_map(fn ($c) => ['color' => $c, 'rows' => []], $palette);

        for ($y = 0; $y < $height; $y++) {
            $nearestForRow = [];
            for ($x = 0; $x < $width; $x++) {
                $px = $colors[$y][$x];
                if (!$this->pxOpaque($px)) {
                    $nearestForRow[] = -1;
                    continue;
                }

                $key5 = (($this->pxRed($px) >> 3) << 10) | (($this->pxGreen($px) >> 3) << 5) | ($this->pxBlue($px) >> 3);
                $nearestForRow[] = $lut[$key5];
            }

            foreach ($buckets as $i => $bucket) {
                $line = '';
                foreach ($nearestForRow as $idx) {
                    $line .= $idx === $i ? '1' : '0';
                }
                $buckets[$i]['rows'][] = $line;
            }
        }

        return array_map(
            fn (array $bucket) => ['color' => $bucket['color'], 'mask' => $bucket['rows']],
            $buckets
        );
    }

    /**
     * Build a 5-bit RGB lookup of the nearest palette index.
     *
     * @param  array<int, array<int>>  $palette
     * @return array<int, int>
     */
    protected function nearestPaletteLookup(array $palette): array
    {
        $lut = array_fill(0, 1 << 15, 0);

        for ($key5 = 0; $key5 < (1 << 15); $key5++) {
            $pr = (($key5 >> 10) & 0x1F) << 3;
            $pg = (($key5 >> 5) & 0x1F) << 3;
            $pb = ($key5 & 0x1F) << 3;

            $best = 0;
            $bestDist = PHP_INT_MAX;
            foreach ($palette as $i => $color) {
                $dr = $pr - $color[0];
                $dg = $pg - $color[1];
                $db = $pb - $color[2];
                $dist = $dr * $dr + $dg * $dg + $db * $db;
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $best = $i;
                }
            }

            $lut[$key5] = $best;
        }

        return $lut;
    }

    /**
     * Convert a mask to closed boundary loops (merge-squares contouring).
     *
     * @return array<int, array<int, array{0: int, 1: int}>>
     */
    protected function traceBoundaries(array $mask, int $width, int $height): array
    {
        $edges = [];
        $edgeKey = static function (int $ax, int $ay, int $bx, int $by): string {
            return $ax < $bx || ($ax === $bx && $ay <= $by)
                ? $ax.','.$ay.':'.$bx.','.$by
                : $bx.','.$by.':'.$ax.','.$ay;
        };

        $isSet = function (int $x, int $y) use ($mask, $width, $height): bool {
            return $x >= 0 && $y >= 0 && $x < $width && $y < $height && $mask[$y][$x] === '1';
        };

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($mask[$y][$x] !== '1') {
                    continue;
                }

                if (!$isSet($x, $y - 1)) {
                    $k = $edgeKey($x, $y, $x + 1, $y);
                    if (isset($edges[$k])) {
                        unset($edges[$k]);
                    } else {
                        $edges[$k] = [[$x, $y], [$x + 1, $y]];
                    }
                }
                if (!$isSet($x + 1, $y)) {
                    $k = $edgeKey($x + 1, $y, $x + 1, $y + 1);
                    if (isset($edges[$k])) {
                        unset($edges[$k]);
                    } else {
                        $edges[$k] = [[$x + 1, $y], [$x + 1, $y + 1]];
                    }
                }
                if (!$isSet($x, $y + 1)) {
                    $k = $edgeKey($x, $y + 1, $x + 1, $y + 1);
                    if (isset($edges[$k])) {
                        unset($edges[$k]);
                    } else {
                        $edges[$k] = [[$x, $y + 1], [$x + 1, $y + 1]];
                    }
                }
                if (!$isSet($x - 1, $y)) {
                    $k = $edgeKey($x, $y, $x, $y + 1);
                    if (isset($edges[$k])) {
                        unset($edges[$k]);
                    } else {
                        $edges[$k] = [[$x, $y], [$x, $y + 1]];
                    }
                }
            }
        }

        return $this->chainEdges($edges);
    }

    /**
     * Group a set of boundary edges into closed loops.
     *
     * @return array<int, array<int, array{0: int, 1: int}>>
     */
    protected function chainEdges(array $cells): array
    {
        if ($cells === []) {
            return [];
        }

        // Normalize to a vertex-indexed adjacency list keyed by "x,y".
        $edges = [];
        $byVertex = [];

        foreach ($cells as $edge) {
            [$a, $b] = $edge;
            $hash = $this->edgeHash($a, $b);
            $edges[$hash] = $edge;
            $byVertex[$a[0].','.$a[1]][] = $hash;
        }

        $loops = [];
        $used = [];

        foreach ($edges as $hash => $edge) {
            if (isset($used[$hash])) {
                continue;
            }

            [$a, $b] = $edge;
            $loop = [$a, $b];
            $used[$hash] = true;

            $current = $b;
            $guard = 0;
            while (($current[0] !== $a[0] || $current[1] !== $a[1]) && $guard++ < 1_000_000) {
                $key = $current[0].','.$current[1];

                if (!isset($byVertex[$key])) {
                    break;
                }

                $nextHash = null;
                $next = null;

                foreach ($byVertex[$key] as $candidateHash) {
                    if (isset($used[$candidateHash])) {
                        continue;
                    }

                    $candidateEdge = $edges[$candidateHash];
                    // Prefer the edge that starts where we currently are.
                    if ($candidateEdge[0][0] === $current[0] && $candidateEdge[0][1] === $current[1]) {
                        $nextHash = $candidateHash;
                        $next = $candidateEdge[1];
                        break;
                    }

                    if ($next === null) {
                        $nextHash = $candidateHash;
                        $next = $candidateEdge[1];
                    }
                }

                if ($next === null) {
                    break;
                }

                $used[$nextHash] = true;
                $loop[] = $next;
                $current = $next;
            }

            $ring = $this->dedupeRing($loop);

            if (count($ring) >= 3) {
                $loops[] = $ring;
            }
        }

        return $loops;
    }

    protected function edgeHash(array $a, array $b): string
    {
        return $a[0].','.$a[1].':'.$b[0].','.$b[1];
    }

    /**
     * Remove duplicated consecutive vertices that close the ring.
     *
     * @param  array<int, array{0: int, 1: int}>  $loop
     * @return array<int, array{0: int, 1: int}>
     */
    protected function dedupeRing(array $loop): array
    {
        $ring = [];
        foreach ($loop as $point) {
            $last = end($ring);
            if ($last !== false && $last[0] === $point[0] && $last[1] === $point[1]) {
                continue;
            }
            $ring[] = $point;
        }

        $n = count($ring);
        if ($n > 2) {
            $first = $ring[0];
            $last = $ring[$n - 1];
            if ($first[0] === $last[0] && $first[1] === $last[1]) {
                array_pop($ring);
            }
        }

        return $ring;
    }

    /**
     * Simplify a ring: remove collinear points then run Ramer-Douglas-Peucker.
     *
     * @param  array<int, array{0: int, 1: int}>  $loop
     * @return array<int, array{0: int, 1: int}>
     */
    protected function simplifyLoop(array $loop, float $tolerance): array
    {
        $n = count($loop);
        if ($n <= 4) {
            return $loop;
        }

        $reduced = [$loop[0]];
        for ($i = 1; $i < $n - 1; $i++) {
            $p0 = $loop[$i - 1];
            $p1 = $loop[$i];
            $p2 = $loop[$i + 1];

            if (!$this->isCollinear($p0, $p1, $p2)) {
                $reduced[] = $p1;
            }
        }
        $reduced[] = $loop[$n - 1];

        if (count($reduced) <= 4) {
            return $reduced;
        }

        $tolerance = max(0.15, $tolerance);

        return $this->douglasPeucker($reduced, 0, count($reduced) - 1, $tolerance);
    }

    protected function isCollinear(array $a, array $b, array $c): bool
    {
        $cross = ($c[0] - $a[0]) * ($b[1] - $a[1]) - ($c[1] - $a[1]) * ($b[0] - $a[0]);

        return abs($cross) < 1e-9;
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $points
     * @return array<int, array{0: int, 1: int}>
     */
    protected function douglasPeucker(array $points, int $start, int $end, float $tolerance): array
    {
        if ($end <= $start + 1) {
            return [$points[$start], $points[$end]];
        }

        $maxDist = 0.0;
        $index = $start;
        for ($i = $start + 1; $i < $end; $i++) {
            $dist = $this->perpendicularDistance($points[$i], $points[$start], $points[$end]);
            if ($dist > $maxDist) {
                $maxDist = $dist;
                $index = $i;
            }
        }

        if ($maxDist > $tolerance) {
            $left = $this->douglasPeucker($points, $start, $index, $tolerance);
            $right = $this->douglasPeucker($points, $index, $end, $tolerance);

            return array_merge(array_slice($left, 0, -1), $right);
        }

        return [$points[$start], $points[$end]];
    }

    protected function perpendicularDistance(array $p, array $a, array $b): float
    {
        $dx = $b[0] - $a[0];
        $dy = $b[1] - $a[1];

        if ($dx == 0 && $dy == 0) {
            return sqrt(($p[0] - $a[0]) ** 2 + ($p[1] - $a[1]) ** 2);
        }

        return abs($dy * $p[0] - $dx * $p[1] + $b[0] * $a[1] - $b[1] * $a[0])
            / sqrt($dx * $dx + $dy * $dy);
    }

    /**
     * Convert a vertex ring into SVG path data.
     *
     * @param  array<int, array{0: int, 1: int}>  $loop
     */
    protected function loopToPathData(array $loop): string
    {
        if ($loop === []) {
            return '';
        }

        $format = fn (float $v) => rtrim(rtrim(number_format($v, 1), '0'), '.');

        $d = 'M'.$format($loop[0][0] + 0.5).' '.$format($loop[0][1] + 0.5);

        for ($i = 1, $count = count($loop); $i < $count; $i++) {
            $d .= 'L'.$format($loop[$i][0] + 0.5).' '.$format($loop[$i][1] + 0.5);
        }

        return $d.'Z';
    }
}