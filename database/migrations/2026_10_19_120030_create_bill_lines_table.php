<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('amount');
            $table->string('payer', 20);
            $table->boolean('waived')->default(false);
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_lines');
    }
};
