<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theatre_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('procedure');
            $table->timestamp('scheduled_at');
            $table->string('status', 20)->default('booked');
            $table->foreignId('surgeon_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theatre_bookings');
    }
};
