<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('imaging_study_id')->constrained();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('ordered');
            $table->string('clinical_notes')->nullable();
            $table->text('report')->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at')->nullable();
            $table->foreignId('bill_line_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_orders');
    }
};
