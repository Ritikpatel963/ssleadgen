<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_jobs_tracking', function (Blueprint $table) {
            $table->unsignedInteger('processed_count')->default(0)->after('total_rows');
            $table->unsignedInteger('failed_count')->default(0)->after('skipped_count');
            $table->json('source_headers')->nullable()->after('skipped_details');
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('import_job_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_tracking_id')->constrained('import_jobs_tracking')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('row_data');
            $table->text('reason');
            $table->timestamps();
            $table->unique(['import_job_tracking_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_job_failures');

        Schema::table('import_jobs_tracking', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropColumn(['processed_count', 'failed_count', 'source_headers']);
        });
    }
};