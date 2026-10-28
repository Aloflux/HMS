<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 30);
            $table->text('body');
            $table->string('purpose', 30);
            $table->string('status', 20)->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
