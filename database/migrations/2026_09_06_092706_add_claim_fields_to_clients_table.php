<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('claim_type')->nullable()->after('inforce_finished_date');
            $table->string('claim_admin')->nullable()->after('claim_type');
            $table->date('claim_processed_date')->nullable()->after('claim_admin');
            $table->string('claim_update_status')->nullable()->after('claim_processed_date');
            $table->date('claim_approved_date')->nullable()->after('claim_update_status');
            $table->string('claim_result')->nullable()->after('claim_approved_date');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'claim_type', 'claim_admin', 'claim_processed_date',
                'claim_update_status', 'claim_approved_date', 'claim_result',
            ]);
        });
    }
};
