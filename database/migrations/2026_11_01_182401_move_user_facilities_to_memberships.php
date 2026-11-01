<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A person now works at facilities through memberships, so the facility, role, permissions and department
 * move off the user. Platform operators keep a flag on the user instead of a role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false)->after('password');
            $table->string('password')->nullable()->change();
        });

        DB::table('users')->where('role', 'platform_admin')->update(['is_platform_admin' => true]);

        DB::table('users')->whereNotNull('facility_id')->orderBy('id')->each(function (object $user): void {
            DB::table('memberships')->insert([
                'facility_id' => $user->facility_id,
                'user_id' => $user->id,
                'role' => $user->role,
                'permissions' => $user->permissions,
                'department_id' => $user->department_id,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'accepted_at' => $user->created_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        });

        DB::table('users')->whereNotNull('facility_id')->update(['is_active' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['role', 'permissions']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('facility_id')->nullable()->after('remember_token')->constrained()->nullOnDelete();
            $table->string('role', 40)->default('administrator')->after('facility_id');
            $table->json('permissions')->nullable()->after('role');
            $table->foreignId('department_id')->nullable()->after('phone')->constrained()->nullOnDelete();
        });

        DB::table('memberships')->orderBy('id')->each(function (object $membership): void {
            DB::table('users')->where('id', $membership->user_id)->whereNull('facility_id')->update([
                'facility_id' => $membership->facility_id,
                'role' => $membership->role,
                'permissions' => $membership->permissions,
                'department_id' => $membership->department_id,
                'is_active' => $membership->is_active,
            ]);
        });

        DB::table('users')->where('is_platform_admin', true)->update(['role' => 'platform_admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_platform_admin');
            $table->string('password')->nullable(false)->change();
        });
    }
};
