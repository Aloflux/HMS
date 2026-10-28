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
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
