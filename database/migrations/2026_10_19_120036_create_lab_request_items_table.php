<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('lab_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_test_id')->constrained();
            $table->string('status', 20)->default('ordered');
            $table->string('result_value')->nullable();
            $table->string('result_flag', 20)->nullable();
            $table->foreignId('resulted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resulted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('bill_line_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_request_items');
    }
};
