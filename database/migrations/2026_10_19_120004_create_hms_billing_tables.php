<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->string('status', 20)->default('open');
            $table->string('currency', 3)->default('UGX');
            $table->unsignedInteger('waived_amount')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique('visit_id');
        });

        Schema::create('bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('amount');
            $table->string('payer', 20);
            $table->boolean('waived')->default(false);
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
        });

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

        Schema::create('waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('bill_id')->constrained();
            $table->unsignedInteger('amount');
            $table->string('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waivers');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('cashier_shifts');
        Schema::dropIfExists('bill_lines');
        Schema::dropIfExists('bills');
    }
};
