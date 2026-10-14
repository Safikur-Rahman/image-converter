<?php

namespace App\Http\Controllers;

use App\Models\Conversion;
use App\Models\ConversionFile;
use App\Services\Storage\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    public function __construct(private StorageService $storage) {}

    public function original(string $uuid): Response
    {
        $conversion = Conversion::where('uuid', $uuid)->firstOrFail();
        $this->authorize('download', $conversion);

        return $this->stream($conversion->originalFile(), $conversion, 'original');
    }

    public function svg(string $uuid): Response
    {
        $conversion = Conversion::where('uuid', $uuid)->firstOrFail();
        $this->authorize('download', $conversion);

        return $this->stream($conversion->svgFile(), $conversion, 'svg');
    }

    public function backgroundRemoved(string $uuid): Response
    {
        $conversion = Conversion::where('uuid', $uuid)->firstOrFail();
        $this->authorize('download', $conversion);

        return $this->stream($conversion->backgroundRemovedFile(), $conversion, 'background_removed');
    }

    /**
     * Stream the original raster inline, downscaled on the fly so the browser
     * never has to decode a multi-thousand-pixel photo just to show a preview.
     */
    public function previewOriginal(string $uuid): Response
    {
        $conversion = Conversion::where('uuid', $uuid)->firstOrFail();
        $this->authorize('download', $conversion);

        $file = $conversion->originalFile();

        if ($file === null) {
            abort(404, 'This file is not available yet.');
        }

        return $this->inlinePicture($file);
    }

    /**
     * Stream a picture file inline, downscaled to the preview dimension.
     */
    protected function inlinePicture(ConversionFile $file): Response
    {
        $path = $this->storage->disk()->path($file->path);

        if (!is_file($path)) {
            abort(404, 'The file is no longer available.');
        }

        set_time_limit(120);

        $maxDim = (int) config('imageconverter.max_preview_dimension', 1600);

        $image = @imagecreatefromstring((string) file_get_contents($path));
        if ($image === false) {
            abort(415, 'The file cannot be previewed.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $largest = max($width, $height);

        if ($largest > $maxDim) {
            $scale = $maxDim / $largest;
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        return response()->stream(function () use ($image): void {
            imagejpeg($image, null, 82);
            imagedestroy($image);
        }, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Stream the SVG with an inline disposition so the show page can embed it
     * as an <img> src without hitting browser data-URI size limits.
     */
    public function previewSvg(string $uuid): Response
    {
        $conversion = Conversion::where('uuid', $uuid)->firstOrFail();
        $this->authorize('download', $conversion);

        $file = $conversion->svgFile();

        if ($file === null) {
            abort(404, 'This file is not available yet.');
        }

        $path = $this->storage->disk()->path($file->path);

        if (!is_file($path)) {
            abort(404, 'The file is no longer available.');
        }

        return response()->file($path, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    protected function stream(?ConversionFile $file, Conversion $conversion, ?string $type): Response
    {
        if ($file === null) {
            abort(404, 'This file is not available yet.');
        }

        $path = $this->storage->disk()->path($file->path);

        if (!is_file($path)) {
            // Object storage fallback: try a temporary signed URL.
            $temporary = $this->storage->temporaryUrl($file->path, 5);

            if ($temporary) {
                return response()->redirectTo($temporary);
            }

            abort(404, 'The file is no longer available.');
        }

        $base = StorageService::secureDownloadName($conversion->original_filename);
        $downloadName = $base.'.'.pathinfo($file->filename, PATHINFO_EXTENSION);

        $this->trackDownload($conversion, $file, $type, request());

        return response()->download($path, $downloadName, [
            'Content-Type' => $file->mime_type ?? 'application/octet-stream',
        ]);
    }

    protected function trackDownload(Conversion $conversion, ConversionFile $file, ?string $type, Request $request): void
    {
        \App\Models\Download::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $conversion->user_id,
            'conversion_id' => $conversion->id,
            'conversion_file_id' => $file->id,
            'type' => $type ?? $file->type,
            'ip_hash' => $request->ip() ? Hash::make('ic|'.$request->ip()) : null,
            'user_agent' => Str::limit((string) $request->userAgent(), 500),
        ]);
    }

    private function authorizeHelper(Conversion $conversion): void
    {
        $this->authorize('download', $conversion);
    }
}