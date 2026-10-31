<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('request_date');
            $table->string('client_name');
            $table->string('company')->nullable();
            $table->string('request_type');
            $table->string('process_status')->nullable();
            $table->string('process_by')->nullable();
            $table->text('outcome')->nullable();
            $table->text('comments')->nullable();
            $table->date('finished_date')->nullable();
            $table->string('status')->default('Pending');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_service_requests');
    }
};