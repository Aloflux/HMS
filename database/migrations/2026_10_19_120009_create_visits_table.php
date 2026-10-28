<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->string('visit_number', 30);
            $table->string('visit_type', 20);
            $table->string('status', 30);
            $table->string('priority', 20)->default('routine');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payer', 20);
            $table->foreignId('insurance_scheme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_number')->nullable();
            $table->string('chief_complaint')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['facility_id', 'visit_number']);
            $table->index(['facility_id', 'status']);
            $table->index(['facility_id', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
