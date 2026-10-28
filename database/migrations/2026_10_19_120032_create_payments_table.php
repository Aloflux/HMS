<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('bill_id')->constrained();
            $table->foreignId('cashier_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 20);
            $table->unsignedInteger('amount');
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('confirmed');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['facility_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
