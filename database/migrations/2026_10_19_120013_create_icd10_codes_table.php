<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icd10_codes', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('title');
            $table->string('chapter')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icd10_codes');
    }
};
