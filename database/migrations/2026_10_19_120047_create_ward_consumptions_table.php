<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ward_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stock_location_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->string('billing_type', 30);
            $table->foreignId('bill_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ward_consumptions');
    }
};
