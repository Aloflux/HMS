<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->string('key', 40);
            $table->unsignedInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['facility_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
