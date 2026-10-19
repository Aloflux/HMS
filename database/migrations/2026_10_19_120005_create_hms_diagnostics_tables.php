<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('service_id')->constrained();
            $table->string('code', 30);
            $table->string('name');
            $table->string('specimen', 40)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('reference_text')->nullable();
            $table->foreignId('reagent_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->unsignedInteger('reagent_quantity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['facility_id', 'code']);
        });

        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('ordered');
            $table->string('clinical_notes')->nullable();
            $table->timestamps();
        });

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

        Schema::create('imaging_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('service_id')->constrained();
            $table->string('code', 30);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['facility_id', 'code']);
        });

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

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->string('dose', 60);
            $table->string('frequency', 60);
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->unsignedInteger('quantity');
            $table->string('status', 20)->default('pending');
            $table->foreignId('substituted_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dispensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('prescription_item_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('stock_batch_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('bill_line_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensations');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('imaging_orders');
        Schema::dropIfExists('imaging_studies');
        Schema::dropIfExists('lab_request_items');
        Schema::dropIfExists('lab_requests');
        Schema::dropIfExists('lab_tests');
    }
};
