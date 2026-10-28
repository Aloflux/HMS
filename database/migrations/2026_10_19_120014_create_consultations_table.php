<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('history')->nullable();
            $table->text('examination')->nullable();
            $table->text('plan')->nullable();
            $table->text('referral')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
