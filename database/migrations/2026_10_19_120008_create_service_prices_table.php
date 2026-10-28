<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_scheme_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->unique(['service_id', 'insurance_scheme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
