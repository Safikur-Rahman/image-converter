<?php

namespace App\Services\BackgroundRemoval;

/**
 * Local provider that performs a best-effort, deterministic deskew of the most
 * common case: a near-white or near-black background. It is NOT a substitute
 * for a real AI service and should only be enabled explicitly for offline
 * development. When it cannot decide a background, it leaves the image
 * untouched and reports failure rather than damaging the source.
 */
class LocalProvider implements ProviderInterface
{
    public function name(): string
    {
        return 'local';
    }

    public function removeBackground(string $inputPath, string $outputPath): string
    {
        $image = @imagecreatefromstring((string) file_get_contents($inputPath));
        if ($image === false) {
            throw new \RuntimeException('Unable to decode the image for local background removal.');
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);

            // Sample the four corners to infer the background tone.
            $samples = [
                $this->rgba($image, 0, 0),
                $this->rgba($image, $width - 1, 0),
                $this->rgba($image, 0, $height - 1),
                $this->rgba($image, $width - 1, $height - 1),
            ];

            $cornerAvg = array_map(fn ($i) => (int) round(array_sum(array_column($samples, $i)) / count($samples)), ['r', 'g', 'b']);
            $cornerLum = (int) round(0.299 * $cornerAvg['r'] + 0.587 * $cornerAvg['g'] + 0.114 * $cornerAvg['b']);

            $nearWhite = $cornerLum > 220;
            $nearBlack = $cornerLum < 45;

            if (!$nearWhite && !$nearBlack) {
                throw new \RuntimeException(
                    'Local background removal could not infer a solid near-white or near-black background; configure a real provider instead.'
                );
            }

            $canvas = imagecreatetruecolor($width, $height);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $px = $this->rgba($image, $x, $y);
                    $lum = (int) round(0.299 * $px['r'] + 0.587 * $px['g'] + 0.114 * $px['b']);

                    $matches = $nearWhite ? $lum >= 235 : $lum <= 25;

                    if ($matches) {
                        imagesetpixel($canvas, $x, $y, $transparent);
                    } else {
                        $color = imagecolorallocatealpha($canvas, $px['r'], $px['g'], $px['b'], $px['a'] & 0x7F);
                        imagesetpixel($canvas, $x, $y, $color);
                    }
                }
            }

            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            imagepng($canvas, $outputPath, 9);
            imagedestroy($canvas);

            return $outputPath;
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * @return array{r: int, g: int, b: int, a: int}
     */
    private function rgba(\GdImage $image, int $x, int $y): array
    {
        $rgb = imagecolorat($image, $x, $y);

        return [
            'r' => ($rgb >> 16) & 0xFF,
            'g' => ($rgb >> 8) & 0xFF,
            'b' => $rgb & 0xFF,
            'a' => (($rgb >> 24) & 0x7F),
        ];
    }
}