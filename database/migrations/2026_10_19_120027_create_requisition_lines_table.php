<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_issued')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_lines');
    }
};
