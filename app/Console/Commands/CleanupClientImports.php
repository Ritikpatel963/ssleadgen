<?php

namespace App\Console\Commands;

use App\Models\ImportJobTracking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupClientImports extends Command
{
    protected $signature = 'imports:cleanup {--retention=30 : Days to retain completed import results}';

    protected $description = 'Remove expired client import results and abandoned uploads';

    public function handle(): int
    {
        $retentionDays = max(1, (int) $this->option('retention'));
        $resultsCutoff = now()->subDays($retentionDays);
        $removedResults = 0;

        ImportJobTracking::query()
            ->whereNotIn('status', ['pending', 'processing'])
            ->where('updated_at', '<', $resultsCutoff)
            ->chunkById(100, function ($imports) use (&$removedResults) {
                foreach ($imports as $import) {
                    if ($import->file_path && Storage::exists($import->file_path)) {
                        Storage::delete($import->file_path);
                    }
                    $import->delete();
                    $removedResults++;
                }
            });

        $activePaths = array_fill_keys(
            ImportJobTracking::query()
                ->whereIn('status', ['pending', 'processing'])
                ->whereNotNull('file_path')
                ->pluck('file_path')
                ->all(),
            true
        );
        $orphanCutoff = now()->subDay()->timestamp;
        $removedUploads = 0;

        foreach (Storage::allFiles('temp/imports') as $path) {
            if (!isset($activePaths[$path]) && Storage::lastModified($path) < $orphanCutoff) {
                Storage::delete($path);
                $removedUploads++;
            }
        }

        $this->info("Removed {$removedResults} expired import results and {$removedUploads} abandoned uploads.");

        return self::SUCCESS;
    }
}