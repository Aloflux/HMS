<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 12)->unique();
            $table->string('kind', 30);
            $table->string('country', 2)->default('UG');
            $table->string('district');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('bed_capacity')->default(0);
            $table->string('moh_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
