<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('insurance_scheme_id')->constrained();
            $table->string('number', 30);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('total_amount')->default(0);
            $table->unsignedInteger('remitted_amount')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
