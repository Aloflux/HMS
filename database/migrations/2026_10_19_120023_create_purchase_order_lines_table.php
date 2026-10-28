<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('pack_size')->default(1);
            $table->unsignedInteger('unit_cost');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
