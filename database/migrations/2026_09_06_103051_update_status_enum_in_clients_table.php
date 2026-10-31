<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('Inforce','Inactive','Cancellation','NPW Deferred','Login Client','Claims') DEFAULT 'Inforce'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('Inforce','Inactive','Cancellation','NPW Deferred','Login Client') DEFAULT 'Inforce'");
    }
};
