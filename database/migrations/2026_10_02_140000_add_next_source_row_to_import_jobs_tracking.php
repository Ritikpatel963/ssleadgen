<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_jobs_tracking', function (Blueprint $table) {
            $table->unsignedInteger('next_source_row')->default(2)->after('sheet_index');
        });
    }

    public function down(): void
    {
        Schema::table('import_jobs_tracking', function (Blueprint $table) {
            $table->dropColumn('next_source_row');
        });
    }
};