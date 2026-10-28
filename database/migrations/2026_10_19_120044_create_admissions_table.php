<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('ward_id')->constrained();
            $table->foreignId('bed_id')->constrained();
            $table->foreignId('admitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admitted_at');
            $table->timestamp('discharged_at')->nullable();
            $table->text('discharge_summary')->nullable();
            $table->string('discharge_outcome', 20)->nullable();
            $table->string('diagnosis_summary')->nullable();
            $table->string('status', 20)->default('admitted');
            $table->timestamps();

            $table->index(['facility_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
