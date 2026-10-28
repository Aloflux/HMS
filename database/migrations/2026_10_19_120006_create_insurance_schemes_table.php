<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_schemes');
    }
};
