<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('policy_no')->nullable();
            $table->string('company')->nullable();
            $table->date('login_date')->nullable();
            $table->decimal('anp', 10, 2)->nullable();
            $table->date('dob')->nullable();
            $table->string('address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('city')->nullable();
            $table->string('post_code')->nullable();
            $table->string('adviser')->nullable();
            $table->string('not_counting')->nullable();
            $table->string('compliance_by')->nullable();
            $table->date('roa_due_date')->nullable();
            $table->string('status_compliance')->nullable();
            $table->string('sent_to_client')->nullable();
            $table->string('outcome')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'policy_no', 'company', 'login_date', 'anp', 'dob',
                'address', 'suburb', 'city', 'post_code', 'adviser',
                'not_counting', 'compliance_by', 'roa_due_date',
                'status_compliance', 'sent_to_client', 'outcome',
            ]);
        });
    }
};
