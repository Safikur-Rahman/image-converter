<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class AppSettings
{
    /**
     * Default settings used when nothing is stored in the database.
     *
     * @var array<string, mixed>
     */
    protected const DEFAULTS = [
        'max_upload_size' => null,      // KB, null => config('imageconverter.max_upload_size_kb')
        'allowed_formats' => null,      // comma separated, null => config()
        'conversion_limits' => null,    // per-plan overrides are in the plans table
        'background_removal_available' => null,
        'default_svg_quality' => null,
        'storage_retention_days' => null,
        'maintenance_message' => null,
    ];

    private const CACHE_KEY = 'imageconverter.settings';

    public function all(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $db = Cache::remember(self::CACHE_KEY, 300, function () {
            return \App\Models\AppSetting::pluck('value', 'key')->all();
        });

        return array_merge(self::DEFAULTS, $db);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        \App\Models\AppSetting::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );

        Cache::forget(self::CACHE_KEY);
    }

    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function maxUploadSizeBytes(): int
    {
        $kb = $this->get('max_upload_size', config('imageconverter.max_upload_size_kb'));

        return (int) $kb * 1024;
    }

    public function allowedFormats(): array
    {
        $raw = $this->get('allowed_formats', implode(',', config('imageconverter.allowed_formats')));

        if (is_array($raw)) {
            return $raw;
        }

        return array_values(array_filter(array_map('strtolower', array_map('trim', explode(',', (string) $raw)))));
    }

    public function backgroundRemovalAvailable(): bool
    {
        $value = $this->get('background_removal_available', config('imageconverter.background_removal.provider') !== '');

        return (bool) $value;
    }

    public function defaultSvgQuality(): string
    {
        return (string) $this->get('default_svg_quality', config('imageconverter.default_svg_quality'));
    }

    public function storageRetentionDays(): int
    {
        return (int) $this->get('storage_retention_days', config('imageconverter.storage_retention_days'));
    }

    public function isMaintenanceMode(): bool
    {
        return (bool) $this->get('maintenance_mode', false);
    }
}