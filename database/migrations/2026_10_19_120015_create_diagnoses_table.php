<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }
};
