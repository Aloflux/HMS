<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->string('batch_number', 40);
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_cost');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};
