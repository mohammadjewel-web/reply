<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        $now = now();
        $exists = DB::table('roles')->where('slug', 'admin')->exists();
        if (! $exists) {
            DB::table('roles')->insert([
                [
                    'name' => 'Administrator',
                    'slug' => 'admin',
                    'description' => 'Full access to messaging tools, team settings, and role management.',
                    'is_system' => true,
                    'grants_admin_panel' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Agent',
                    'slug' => 'agent',
                    'description' => 'Standard team member without admin panel access.',
                    'is_system' => true,
                    'grants_admin_panel' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
        $agentId = DB::table('roles')->where('slug', 'agent')->value('id');

        foreach (DB::table('users')->cursor() as $user) {
            $roleId = $user->is_admin ? $adminId : $agentId;
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $user->id, 'role_id' => $roleId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($adminId) {
            $userIds = DB::table('role_user')->where('role_id', $adminId)->pluck('user_id');
            DB::table('users')->whereIn('id', $userIds)->update(['is_admin' => true]);
        }

        DB::table('role_user')->delete();
        DB::table('roles')->delete();
    }
};
