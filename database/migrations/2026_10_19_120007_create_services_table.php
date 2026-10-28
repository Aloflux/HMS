<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
