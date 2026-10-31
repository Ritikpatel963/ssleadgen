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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('policy_no')->nullable()->after('id');
            $table->string('company')->nullable()->after('policy_no');
            $table->date('login_date')->nullable()->after('address');
            $table->decimal('anp', 10, 2)->nullable()->after('login_date');
            $table->string('suburb')->nullable()->after('address');
            $table->string('city')->nullable()->after('suburb');
            $table->string('post_code')->nullable()->after('city');
            $table->string('adviser')->nullable()->after('user_id');
            $table->boolean('not_counting')->default(false)->after('adviser');
            $table->string('compliance_by')->nullable()->after('not_counting');
            $table->date('roa_due_date')->nullable()->after('compliance_by');
            $table->string('status_compliance')->nullable()->after('roa_due_date');
            $table->string('sent_to_client')->default('Pending')->after('status_compliance');
            $table->string('outcome')->nullable()->after('sent_to_client');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'policy_no', 'company', 'login_date', 'anp',
                'suburb', 'city', 'post_code', 'adviser',
                'not_counting', 'compliance_by', 'roa_due_date',
                'status_compliance', 'sent_to_client', 'outcome',
            ]);
        });
    }
};
