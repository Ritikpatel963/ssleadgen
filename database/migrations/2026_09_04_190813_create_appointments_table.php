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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('client_name');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('Scheduled');
            $table->string('color')->default('event-blue');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
