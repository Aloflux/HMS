<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each facility a person works at gets its own password. Existing members keep the password they have today,
 * and only platform operators keep a password on the account itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('password')->nullable()->after('department_id');
            $table->string('password_reset_token', 64)->nullable()->unique()->after('password');
            $table->timestamp('password_reset_sent_at')->nullable()->after('password_reset_token');
        });

        DB::table('memberships')
            ->whereNotNull('accepted_at')
            ->update(['password' => DB::table('users')->select('password')->whereColumn('users.id', 'memberships.user_id')]);

        DB::table('users')->where('is_platform_admin', false)->update(['password' => null]);
    }

    public function down(): void
    {
        DB::table('memberships')->whereNotNull('password')->orderBy('id')->each(function (object $membership): void {
            DB::table('users')->where('id', $membership->user_id)->whereNull('password')->update(['password' => $membership->password]);
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropUnique(['password_reset_token']);
            $table->dropColumn(['password', 'password_reset_token', 'password_reset_sent_at']);
        });
    }
};
