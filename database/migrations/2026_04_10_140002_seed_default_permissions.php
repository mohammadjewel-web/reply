<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [
            ['name' => 'Inbox', 'slug' => 'inbox.access', 'description' => 'View and reply in the unified chats inbox.', 'is_system' => true],
            ['name' => 'WhatsApp', 'slug' => 'whatsapp.manage', 'description' => 'Connect and configure WhatsApp Cloud API.', 'is_system' => true],
            ['name' => 'Messenger', 'slug' => 'messenger.manage', 'description' => 'Connect and configure Facebook Messenger.', 'is_system' => true],
            ['name' => 'Employees', 'slug' => 'employees.manage', 'description' => 'Assign roles to team members.', 'is_system' => true],
            ['name' => 'Roles', 'slug' => 'roles.manage', 'description' => 'Create and edit roles and their permissions.', 'is_system' => true],
            ['name' => 'Permissions', 'slug' => 'permissions.manage', 'description' => 'Create and edit permission definitions.', 'is_system' => true],
            ['name' => 'Integration settings', 'slug' => 'settings.integrations', 'description' => 'View webhook URLs and integration shortcuts on Settings.', 'is_system' => true],
        ];

        foreach ($rows as $row) {
            $existing = DB::table('permissions')->where('slug', $row['slug'])->first();
            if ($existing) {
                DB::table('permissions')->where('id', $existing->id)->update([
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'is_system' => $row['is_system'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('permissions')->insert([
                    ...$row,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        if (! $adminRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $adminRoleId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        $slugs = [
            'inbox.access',
            'whatsapp.manage',
            'messenger.manage',
            'employees.manage',
            'roles.manage',
            'permissions.manage',
            'settings.integrations',
        ];
        $ids = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }
};
