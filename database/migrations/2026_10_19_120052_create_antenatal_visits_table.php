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
    }

    public function down(): void
    {
        Schema::dropIfExists('antenatal_visits');
    }
};
