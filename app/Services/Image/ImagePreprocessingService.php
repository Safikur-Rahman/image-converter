<?php

namespace App\Services\Image;

use GdImage;

/**
 * Modular image preprocessing. Each operation is a tiny, testable unit and the
 * pipeline is assembled explicitly instead of living inside a controller.
 */
class ImagePreprocessingService
{
    public function __construct(protected int $maxDimension = 0)
    {
        if ($this->maxDimension === 0) {
            $this->maxDimension = (int) config('imageconverter.max_trace_dimension', 2048);
        }
    }

    /**
     * Run a standard pipeline: decode, normalize transparency, resize to the
     * trace dimension, and finally re-encode to PNG.
     *
     * @param  string  $path  Absolute path of the source file.
     * @return array{png_path: string, width: int, height: int}  absolute temp png
     */
    public function prepareForTracing(string $path, int $userId, string $uuid): array
    {
        $image = $this->load($path);
        $image = $this->resizeWithin($image);

        $width = imagesx($image);
        $height = imagesy($image);

        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.$uuid.'_trace.png';
        imagepng($image, $tmp, 9);
        imagedestroy($image);

        return ['png_path' => $tmp, 'width' => $width, 'height' => $height];
    }

    public function load(string $path): GdImage
    {
        $data = file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException('Could not read the source file.');
        }

        $image = imagecreatefromstring($data);
        if ($image === false) {
            throw new \RuntimeException('Unsupported or corrupt image data.');
        }

        return $image;
    }

    /**
     * Flatten the image onto a transparent layer while preserving any existing
     * alpha. Used before SVG tracing so transparency survives.
     */
    public function normalizeTransparency(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        return $canvas;
    }

    /**
     * Downsizing protection — keeps vectorization fast and stable for large
     * uploads while preserving the viewBox dimensions of the output.
     */
    public function resizeWithin(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $largest = max($width, $height);

        if ($largest <= $this->maxDimension) {
            return $this->normalizeTransparency($image);
        }

        $ratio = $this->maxDimension / $largest;
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Convert an image to grayscale in place (returns a new image).
     */
    public function toGrayscale(GdImage $image): GdImage
    {
        imagefilter($image, IMG_FILTER_GRAYSCALE);

        return $image;
    }

    /**
     * Reduce noise / soften edges for lower quality traces.
     */
    public function soften(GdImage $image, float $sigma = 0.5): GdImage
    {
        if ($sigma > 0.0) {
            $radius = (int) round($sigma);
            if ($radius >= 1) {
                imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
            }
        }

        return $image;
    }

    /**
     * @return array{min: int, max: int} Raw min/max of the luminance channel.
     */
    public function contrastRange(GdImage $image): array
    {
        $min = 255;
        $max = 0;
        $width = imagesx($image);
        $height = imagesy($image);

        for ($y = 0; $y < $height; $y += 3) {
            for ($x = 0; $x < $width; $x += 3) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $l = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);
                $min = min($min, $l);
                $max = max($max, $l);
            }
        }

        return ['min' => $min, 'max' => $max];
    }
}