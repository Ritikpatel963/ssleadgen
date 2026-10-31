<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index('updated_at', 'leads_updated_at_index');
            $table->index(['status', 'updated_at'], 'leads_status_updated_at_index');
            $table->index('created_at', 'leads_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_updated_at_index');
            $table->dropIndex('leads_status_updated_at_index');
            $table->dropIndex('leads_created_at_index');
        });
    }
};
