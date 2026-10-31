<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_jobs_tracking', function (Blueprint $table) {
            $table->unsignedInteger('sheet_index')->default(0)->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('import_jobs_tracking', function (Blueprint $table) {
            $table->dropColumn('sheet_index');
        });
    }
};