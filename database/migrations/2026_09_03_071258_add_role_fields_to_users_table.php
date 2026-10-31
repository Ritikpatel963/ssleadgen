<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete()->after('id');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('remember_token');
            $table->string('fspr_number')->nullable()->unique()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('fspr_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'status', 'fspr_number', 'last_login_at']);
        });
    }
};
