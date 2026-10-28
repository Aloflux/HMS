<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->string('name');
            $table->string('kind', 30);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['facility_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
