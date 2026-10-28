<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->unsignedSmallInteger('respiration')->nullable();
            $table->unsignedSmallInteger('systolic')->nullable();
            $table->unsignedSmallInteger('diastolic')->nullable();
            $table->unsignedSmallInteger('spo2')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->string('priority', 20);
            $table->string('chief_complaint');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_records');
    }
};
