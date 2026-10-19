<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icd10_codes', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('title');
            $table->string('chapter')->nullable();
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('history')->nullable();
            $table->text('examination')->nullable();
            $table->text('plan')->nullable();
            $table->text('referral')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained();
            $table->string('icd10_code', 10);
            $table->string('title');
            $table->string('rank', 20);
            $table->timestamps();

            $table->index(['facility_id', 'icd10_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('icd10_codes');
    }
};
