<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('opening_float')->default(0);
            $table->unsignedInteger('cash_expected')->nullable();
            $table->unsignedInteger('cash_counted')->nullable();
            $table->integer('variance')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->index(['facility_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
