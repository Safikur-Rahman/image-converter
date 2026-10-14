<?php

return [

    /**
     * Upload and processing limits.
     */
    'max_upload_size_kb' => (int) env('MAX_UPLOAD_SIZE', 10240),
    'max_upload_size_bytes' => (int) env('MAX_UPLOAD_SIZE', 10240) * 1024,
    'max_dimensions' => (int) env('MAX_IMAGE_DIMENSIONS', 8000),

    /**
     * Allowed raster input formats that are verified against the real MIME type.
     */
    'allowed_formats' => array_filter(array_map('strtolower', array_map('trim', array_filter(
        explode(',', (string) env('ALLOWED_FORMATS', 'png,jpg,jpeg,webp'))
    )))),

    /**
     * Default conversion quality when not specified.
     */
    'default_svg_quality' => env('DEFAULT_SVG_QUALITY', 'high'),

    /**
     * Storage retention for temporary files in days. -1 disables cleanup.
     */
    'storage_retention_days' => (int) env('STORAGE_RETENTION_DAYS', 30),

    /**
     * Tracing engine implementation. Currently "pure_php" is bundled.
     */
    'tracing_engine' => env('SVG_TRACING_ENGINE', 'pure_php'),

    /**
     * Max processing dimension for the original image before vectorization.
     * Larger images are downscaled first to keep tracing fast and stable.
     */
    'max_trace_dimension' => (int) env('MAX_TRACE_DIMENSION', 2048),

    /**
     * Max dimension for on-the-fly inline previews. Bigger originals are
     * downscaled before streaming so browsers decode a lightweight image.
     */
    'max_preview_dimension' => (int) env('MAX_PREVIEW_DIMENSION', 1600),

    /**
     * Background removal provider configuration.
     */
    'background_removal' => [
        'provider' => env('BACKGROUND_REMOVAL_PROVIDER', ''),
        'api_key' => env('BACKGROUND_REMOVAL_API_KEY', ''),
        'api_url' => env('BACKGROUND_REMOVAL_API_URL', ''),
        'timeout' => (int) env('BACKGROUND_REMOVAL_TIMEOUT', 90),
    ],

    /**
     * Queue configuration overrides for jobs.
     */
    'queue' => [
        'conversion_retries' => (int) env('CONVERSION_JOB_RETRIES', 3),
        'conversion_timeout' => (int) env('CONVERSION_JOB_TIMEOUT', 300),
        'conversion_backoff' => [5, 15, 60],
        'conversion_memory_limit' => env('CONVERSION_MEMORY_LIMIT', '1024M'),
    ],

];