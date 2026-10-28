<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('service_id')->constrained();
            $table->string('code', 30);
            $table->string('name');
            $table->string('specimen', 40)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('reference_text')->nullable();
            $table->foreignId('reagent_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->unsignedInteger('reagent_quantity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['facility_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
