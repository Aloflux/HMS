<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->timestamps();

            $table->unique(['facility_id', 'code']);
        });

        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('status', 20)->default('available');
            $table->timestamps();

            $table->unique(['ward_id', 'code']);
        });

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

        Schema::create('nursing_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->string('dose', 60);
            $table->timestamp('scheduled_at');
            $table->timestamp('administered_at')->nullable();
            $table->foreignId('administered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('due');
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ward_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stock_location_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->string('billing_type', 30);
            $table->foreignId('bill_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('preauthorisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('insurance_scheme_id')->constrained();
            $table->string('description');
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('pending');
            $table->string('reference')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('insurance_scheme_id')->constrained();
            $table->string('number', 30);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('total_amount')->default(0);
            $table->unsignedInteger('remitted_amount')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'number']);
        });

        Schema::create('claim_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_line_id')->constrained();
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->unique('bill_line_id');
        });

        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('claim_id')->constrained();
            $table->unsignedInteger('amount');
            $table->string('reference');
            $table->timestamp('received_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittances');
        Schema::dropIfExists('claim_lines');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('preauthorisations');
        Schema::dropIfExists('ward_consumptions');
        Schema::dropIfExists('medication_administrations');
        Schema::dropIfExists('nursing_notes');
        Schema::dropIfExists('admissions');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');
    }
};
