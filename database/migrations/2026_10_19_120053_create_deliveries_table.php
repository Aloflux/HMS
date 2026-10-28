<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('delivered_at');
            $table->string('mode', 20);
            $table->string('outcome', 20);
            $table->string('baby_sex', 10)->nullable();
            $table->decimal('birth_weight_kg', 4, 2)->nullable();
            $table->unsignedTinyInteger('apgar')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
