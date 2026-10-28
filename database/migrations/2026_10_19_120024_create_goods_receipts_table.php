<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('purchase_order_id')->constrained();
            $table->string('number', 30);
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
