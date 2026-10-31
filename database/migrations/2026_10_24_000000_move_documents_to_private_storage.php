<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $publicDisk = Storage::disk('public');
        $privateDisk = Storage::disk('local');

        foreach ($publicDisk->allFiles('documents') as $path) {
            if (!$privateDisk->exists($path)) {
                $stream = $publicDisk->readStream($path);
                if (!is_resource($stream)) {
                    throw new RuntimeException("Unable to read public document [{$path}] for migration.");
                }

                try {
                    if (!$privateDisk->writeStream($path, $stream)) {
                        throw new RuntimeException("Unable to move public document [{$path}] to private storage.");
                    }
                } finally {
                    fclose($stream);
                }
            }

            if (!$publicDisk->delete($path)) {
                throw new RuntimeException("Unable to remove public document [{$path}] after migration.");
            }
        }
    }

    public function down(): void
    {
        // Private document storage is not moved back to the public disk on rollback.
    }
};
