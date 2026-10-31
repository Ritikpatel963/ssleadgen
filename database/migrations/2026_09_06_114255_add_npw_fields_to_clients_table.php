<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->date('npw_issue_date')->nullable();
            $table->string('npw_premium')->nullable();
            $table->string('npw_premium_mode')->nullable();
            $table->string('npw_admin')->nullable();
            $table->string('npw_pending')->nullable();
            $table->text('npw_comments')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'npw_issue_date',
                'npw_premium',
                'npw_premium_mode',
                'npw_admin',
                'npw_pending',
                'npw_comments',
            ]);
        });
    }
};
