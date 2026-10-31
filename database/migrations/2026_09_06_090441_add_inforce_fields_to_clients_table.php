<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->date('inforce_date')->nullable()->after('outcome');
            $table->string('inforce_request_type')->nullable()->after('inforce_date');
            $table->string('inforce_process_status')->nullable()->after('inforce_request_type');
            $table->string('inforce_process_by')->nullable()->after('inforce_process_status');
            $table->text('inforce_outcome')->nullable()->after('inforce_process_by');
            $table->text('inforce_comments')->nullable()->after('inforce_outcome');
            $table->date('inforce_finished_date')->nullable()->after('inforce_comments');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'inforce_date', 'inforce_request_type', 'inforce_process_status',
                'inforce_process_by', 'inforce_outcome', 'inforce_comments', 'inforce_finished_date',
            ]);
        });
    }
};
