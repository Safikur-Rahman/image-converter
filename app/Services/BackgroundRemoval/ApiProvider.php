<?php

namespace App\Services\BackgroundRemoval;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * HTTP provider that talks to an external AI background-removal API.
 *
 * The concrete endpoint and authentication scheme are intentionally kept
 * generic: any REST endpoint that accepts an image upload and returns a
 * transparent image can be configured through env values:
 *
 *   BACKGROUND_REMOVAL_PROVIDER=http
 *   BACKGROUND_REMOVAL_API_URL=https://.../remove
 *   BACKGROUND_REMOVAL_API_KEY=...
 */
class ApiProvider implements ProviderInterface
{
    public function __construct(protected array $config = [])
    {
        $this->config = $config ?: config('imageconverter.background_removal');
    }

    public function name(): string
    {
        return 'http';
    }

    public function removeBackground(string $inputPath, string $outputPath): string
    {
        $url = (string) ($this->config['api_url'] ?? '');
        $key = (string) ($this->config['api_key'] ?? '');

        if ($url === '') {
            throw new \RuntimeException('Background removal API URL is not configured.');
        }

        $response = Http::timeout((int) ($this->config['timeout'] ?? 90))
            ->withHeaders([
                'Authorization' => 'Bearer '.$key,
            ])
            ->attach(
                'image',
                file_get_contents($inputPath) ?: '',
                basename($inputPath)
            )
            ->post($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Background removal API returned '.$response->status().': '.Str::limit($response->body(), 200)
            );
        }

        $bytes = $response->body();
        if ($bytes === '') {
            throw new \RuntimeException('Background removal API returned an empty response.');
        }

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $bytes);

        return $outputPath;
    }
}