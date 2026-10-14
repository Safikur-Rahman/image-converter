<?php

namespace App\Services\BackgroundRemoval;

/**
 * Contract every background-removal provider (external AI API or local) must
 * honour so the application never couples to a concrete vendor.
 */
interface ProviderInterface
{
    /**
     * Remove the background from an image and write the result to disk.
     *
     * @return string Path to the resulting transparent PNG.
     */
    public function removeBackground(string $inputPath, string $outputPath): string;

    /**
     * Human readable provider name for logs and database records.
     */
    public function name(): string;
}