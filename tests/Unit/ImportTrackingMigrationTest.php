<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportTrackingMigrationTest extends TestCase
{
    public function test_durable_import_results_migration_adds_and_drops_failure_storage(): void
    {
        Schema::dropIfExists('import_job_failures');
        Schema::dropIfExists('import_jobs_tracking');
        Schema::create('import_jobs_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('destination', 100)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('skipped_details')->nullable();
            $table->timestamps();
        });

        $migration = require base_path('database/migrations/2026_10_02_120000_add_durable_results_to_import_jobs_tracking.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('import_jobs_tracking', 'processed_count'));
        $this->assertTrue(Schema::hasColumn('import_jobs_tracking', 'failed_count'));
        $this->assertTrue(Schema::hasColumn('import_jobs_tracking', 'source_headers'));
        $this->assertTrue(Schema::hasTable('import_job_failures'));

        $sheetMigration = require base_path('database/migrations/2026_10_02_130000_add_sheet_index_to_import_jobs_tracking.php');
        $sheetMigration->up();
        $this->assertTrue(Schema::hasColumn('import_jobs_tracking', 'sheet_index'));
        $sheetMigration->down();
        $this->assertFalse(Schema::hasColumn('import_jobs_tracking', 'sheet_index'));

        $cursorMigration = require base_path('database/migrations/2026_10_02_140000_add_next_source_row_to_import_jobs_tracking.php');
        $cursorMigration->up();
        $this->assertTrue(Schema::hasColumn('import_jobs_tracking', 'next_source_row'));
        $cursorMigration->down();
        $this->assertFalse(Schema::hasColumn('import_jobs_tracking', 'next_source_row'));

        $migration->down();

        $this->assertFalse(Schema::hasColumn('import_jobs_tracking', 'processed_count'));
        $this->assertFalse(Schema::hasTable('import_job_failures'));

        Schema::dropIfExists('import_jobs_tracking');
    }
}