<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->string('status', 20)->default('open');
            $table->string('currency', 3)->default('UGX');
            $table->unsignedInteger('waived_amount')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique('visit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
