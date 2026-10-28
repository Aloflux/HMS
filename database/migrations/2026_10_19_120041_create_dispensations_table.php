<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('prescription_item_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('stock_batch_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('bill_line_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensations');
    }
};
