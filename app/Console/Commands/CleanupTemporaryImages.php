<?php

namespace App\Console\Commands;

use App\Support\AppSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'images:cleanup')]
class CleanupTemporaryImages extends Command
{
    protected $signature = 'images:cleanup {--dry-run : Report without deleting} {--days= : Override retention window}';

    protected $description = 'Remove temporary image files older than the configured retention window.';

    public function handle(AppSettings $settings): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : $settings->storageRetentionDays();

        if ($days < 0) {
            $this->info('Cleanup is disabled (retention < 0). Nothing to do.');

            return self::SUCCESS;
        }

        $threshold = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $disk = Storage::disk(config('filesystems.default'));
        $deleted = 0;
        $bytes = 0;

        foreach (['temporary', 'processed', 'previews'] as $directory) {
            $files = $disk->allFiles($directory);

            foreach ($files as $file) {
                $modified = $disk->lastModified($file);
                $lastModified = \Illuminate\Support\Carbon::createFromTimestamp($modified);

                if ($lastModified->lte($threshold)) {
                    if ($dryRun) {
                        $this->line("Would delete {$file}");
                    } else {
                        $size = $disk->size($file);
                        $disk->delete($file);
                        $bytes += $size;
                    }
                    $deleted++;
                }
            }
        }

        $this->info($dryRun
            ? "Dry run: {$deleted} temporary files would be removed."
            : "Removed {$deleted} temporary files ({$this->humanBytes($bytes)}).");

        return self::SUCCESS;
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        return round($bytes / 1024, 2).' KB';
    }
}