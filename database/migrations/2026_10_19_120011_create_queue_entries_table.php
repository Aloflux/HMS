<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage', 30);
            $table->string('status', 20)->default('waiting');
            $table->string('priority', 20)->default('routine');
            $table->timestamp('queued_at');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'stage', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_entries');
    }
};
