<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('status', 20)->default('available');
            $table->timestamps();

            $table->unique(['ward_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
