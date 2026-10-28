<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('stock_location_id')->constrained();
            $table->string('batch_number', 40);
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('unit_cost')->default(0);
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['stock_location_id', 'item_id', 'batch_number']);
            $table->index(['item_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
