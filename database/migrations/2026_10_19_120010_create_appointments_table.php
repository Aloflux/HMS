<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status', 20)->default('booked');
            $table->string('reason')->nullable();
            $table->timestamp('reminder_queued_at')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
