<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->string('patient_number', 30);
            $table->string('surname');
            $table->string('given_name');
            $table->string('other_name')->nullable();
            $table->string('sex', 10);
            $table->date('date_of_birth');
            $table->string('phone', 30);
            $table->string('national_id', 40)->nullable();
            $table->string('district');
            $table->string('village')->nullable();
            $table->string('next_of_kin_name');
            $table->string('next_of_kin_relationship', 50);
            $table->string('next_of_kin_phone', 30);
            $table->string('blood_group', 5)->nullable();
            $table->string('allergies', 500)->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['facility_id', 'patient_number']);
            $table->index(['facility_id', 'phone']);
            $table->index(['facility_id', 'surname', 'given_name']);
        });

        Schema::create('insurance_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->string('name');
            $table->string('kind', 20);
            $table->unsignedInteger('cover_limit')->nullable();
            $table->boolean('requires_preauthorisation')->default(false);
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->string('code', 30);
            $table->string('name');
            $table->string('category', 30);
            $table->unsignedInteger('cash_price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['facility_id', 'code']);
        });

        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_scheme_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->unique(['service_id', 'insurance_scheme_id']);
        });

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

        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage', 30);
            $table->string('status', 20)->default('waiting');
            $table->string('priority', 20)->default('routine');
            $table->timestamp('queued_at');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'stage', 'status']);
        });

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
        Schema::dropIfExists('queue_entries');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('service_prices');
        Schema::dropIfExists('services');
        Schema::dropIfExists('insurance_schemes');
        Schema::dropIfExists('patients');
    }
};
