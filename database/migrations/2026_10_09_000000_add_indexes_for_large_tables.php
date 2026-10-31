<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->index('created_at', 'clients_created_at_index');
            $table->index(['status', 'created_at'], 'clients_status_created_at_index');
        });

        Schema::table('client_service_requests', function (Blueprint $table) {
            $table->index('created_at', 'client_requests_created_at_index');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index('created_at', 'documents_created_at_index');
        });

        Schema::table('policies', function (Blueprint $table) {
            $table->index('created_at', 'policies_created_at_index');
        });

    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_created_at_index');
            $table->dropIndex('clients_status_created_at_index');
        });

        Schema::table('client_service_requests', function (Blueprint $table) {
            $table->dropIndex('client_requests_created_at_index');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_created_at_index');
        });

        Schema::table('policies', function (Blueprint $table) {
            $table->dropIndex('policies_created_at_index');
        });

    }
};
