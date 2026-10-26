<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Facilities that sign themselves up finish a setup wizard before going live.
     * Existing facilities and accounts were set up by an operator, so they count as onboarded and verified.
     */
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->timestamp('onboarded_at')->nullable()->after('is_active');
        });

        DB::table('facilities')->update(['onboarded_at' => now()]);
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('onboarded_at');
        });
    }
};
