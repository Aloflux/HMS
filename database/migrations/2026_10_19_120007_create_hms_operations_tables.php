<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antenatal_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('gestation_weeks');
            $table->unsignedSmallInteger('fundal_height_cm')->nullable();
            $table->string('fetal_heart', 40)->nullable();
            $table->string('blood_pressure', 20)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('seen_at');
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('delivered_at');
            $table->string('mode', 20);
            $table->string('outcome', 20);
            $table->string('baby_sex', 10)->nullable();
            $table->decimal('birth_weight_kg', 4, 2)->nullable();
            $table->unsignedTinyInteger('apgar')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('theatre_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('procedure');
            $table->timestamp('scheduled_at');
            $table->string('status', 20)->default('booked');
            $table->foreignId('surgeon_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('operation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('theatre_booking_id')->constrained()->cascadeOnDelete();
            $table->text('findings')->nullable();
            $table->text('procedure_notes');
            $table->foreignId('surgeon_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 30);
            $table->text('body');
            $table->string('purpose', 30);
            $table->string('status', 20)->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('operation_notes');
        Schema::dropIfExists('theatre_bookings');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('antenatal_visits');
    }
};
