<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->string('dose', 60);
            $table->timestamp('scheduled_at');
            $table->timestamp('administered_at')->nullable();
            $table->foreignId('administered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('due');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_administrations');
    }
};
