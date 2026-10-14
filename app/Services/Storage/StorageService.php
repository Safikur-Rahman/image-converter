<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    /**
     * Organization of generated files:
     *
     * storage/app/private/
     *   originals/{user_id}/{uuid}.{ext}
     *   processed/{user_id}/{uuid}.png
     *   svg/{user_id}/{uuid}.svg
     *   previews/{user_id}/{uuid}.png
     *   temporary/{user_id}/{uuid}.{ext}
     */
    public function storeOriginal(UploadedFile $file, int $userId, string $uuid): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $this->store($file, "originals/{$userId}", $uuid.'.'.$extension);

        return $this->describe($path, $file->getClientOriginalName(), $file->getMimeType(), $file->getSize());
    }

    public function storeProcessed(string $content, int $userId, string $uuid, string $extension = 'png'): array
    {
        $path = $this->put("processed/{$userId}", "{$uuid}.{$extension}", $content);

        return $this->describe($path, "{$uuid}.{$extension}", $this->mimeFor($extension), strlen($content));
    }

    public function storeSvg(string $content, int $userId, string $uuid): array
    {
        $path = $this->put('svg', "{$uuid}.svg", $content);

        return $this->describe($path, "{$uuid}.svg", 'image/svg+xml', strlen($content));
    }

    public function storePreview(string $content, int $userId, string $uuid): array
    {
        $path = $this->put('previews', "{$uuid}.png", $content);

        return $this->describe($path, "{$uuid}.png", 'image/png', strlen($content));
    }

    public function storeTemporary(string $content, int $userId, string $uuid, string $extension = 'png'): array
    {
        $path = $this->put("temporary/{$userId}", "{$uuid}.{$extension}", $content);

        return $this->describe($path, "{$uuid}.{$extension}", $this->mimeFor($extension), strlen($content));
    }

    protected function store(UploadedFile $file, string $directory, string $name): string
    {
        return $file->storeAs($directory, $name, $this->driver());
    }

    protected function put(string $directory, string $name, string $content): string
    {
        $disk = Storage::disk($this->driver());
        $path = $this->joinPath($directory, $name);

        $disk->put($path, $content);

        return $path;
    }

    public function driver(): string
    {
        return (string) config('imageconverter.filesystem_disk', config('filesystems.default', 'local'));
    }

    public function disk(string $driver = null): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($driver ?? $this->driver());
    }

    public function url(string $path, string $driver = null): string
    {
        return Storage::disk($driver ?? $this->driver())->url($path);
    }

    public function temporaryUrl(string $path, int $minutes = 30, string $driver = null): ?string
    {
        $disk = Storage::disk($driver ?? $this->driver());

        return method_exists($disk, 'temporaryUrl')
            ? $disk->temporaryUrl($path, now()->addMinutes($minutes))
            : null;
    }

    public function read(string $path): ?string
    {
        return Storage::disk($this->driver())->get($path);
    }

    public function deleteByPath(string $path): bool
    {
        if (!$path) {
            return false;
        }

        return Storage::disk($this->driver())->delete($path);
    }

    public function deleteAllForConversion(\App\Models\Conversion $conversion): void
    {
        foreach ($conversion->files as $file) {
            $this->deleteByPath($file->path);
            $file->delete();
        }

        if ($conversion->backgroundRemoval) {
            $this->deleteByPath($conversion->backgroundRemoval->input_path);
            $this->deleteByPath($conversion->backgroundRemoval->output_path);
        }
    }

    /**
     * @return array{path: string, filename: string, disk: string, mime_type: string|null, size: int}
     */
    protected function describe(string $path, string $filename, ?string $mime, int $size): array
    {
        return [
            'path' => $path,
            'filename' => $filename,
            'disk' => $this->driver(),
            'mime_type' => $mime,
            'size' => $size,
        ];
    }

    protected function mimeFor(string $extension): string
    {
        return match (strtolower($extension)) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }

    protected function joinPath(string $directory, string $name): string
    {
        return rtrim(str_replace('\\', '/', $directory), '/').'/'.ltrim($name, '/');
    }

    public static function secureDownloadName(string $original): string
    {
        $base = pathinfo($original, PATHINFO_FILENAME);
        $base = Str::slug($base, '-', 'en');

        // Never allow empty or dangerous names.
        if ($base === '') {
            $base = 'image';
        }

        return $base;
    }
}