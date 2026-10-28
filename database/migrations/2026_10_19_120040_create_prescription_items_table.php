<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->string('dose', 60);
            $table->string('frequency', 60);
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->unsignedInteger('quantity');
            $table->string('status', 20)->default('pending');
            $table->foreignId('substituted_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
