<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->string('sku', 30);
            $table->string('name');
            $table->string('category', 30);
            $table->string('billing_type', 30);
            $table->string('form', 30)->default('other');
            $table->string('unit', 30)->default('unit');
            $table->string('unit_content', 30)->nullable();
            $table->string('pack_unit', 30)->nullable();
            $table->unsignedInteger('pack_size')->default(1);
            $table->unsignedInteger('cash_price')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['facility_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
