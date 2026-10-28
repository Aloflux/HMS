<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_line_id')->constrained();
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->unique('bill_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_lines');
    }
};
