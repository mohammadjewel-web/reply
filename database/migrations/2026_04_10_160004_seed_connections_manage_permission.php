<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $exists = DB::table('permissions')->where('slug', 'connections.manage')->exists();
        if ($exists) {
            return;
        }

        DB::table('permissions')->insert([
            'name' => 'Connections',
            'slug' => 'connections.manage',
            'description' => 'Manage WhatsApp and Messenger channel accounts (credentials, labels, active state).',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permId = DB::table('permissions')->where('slug', 'connections.manage')->value('id');
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($permId && $adminRoleId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permId, 'role_id' => $adminRoleId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        $permId = DB::table('permissions')->where('slug', 'connections.manage')->value('id');
        if ($permId) {
            DB::table('permission_role')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};
