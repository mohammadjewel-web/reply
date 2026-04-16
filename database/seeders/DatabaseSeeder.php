<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Full access to messaging tools, team settings, and role management.',
                'is_system' => true,
                'grants_admin_panel' => true,
            ],
        );

        $agentRole = Role::query()->firstOrCreate(
            ['slug' => 'agent'],
            [
                'name' => 'Agent',
                'description' => 'Standard team member without admin panel access.',
                'is_system' => true,
                'grants_admin_panel' => false,
            ],
        );

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );
        $admin->forceFill(['name' => 'Admin', 'is_active' => true])->save();
        $admin->roles()->sync([$adminRole->id]);

        $allPermissionIds = Permission::query()->pluck('id')->all();
        if ($allPermissionIds !== []) {
            $adminRole->permissions()->sync($allPermissionIds);
        }

        $agent = User::query()->firstOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'Agent',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );
        $agent->forceFill(['name' => 'Agent', 'is_active' => true])->save();
        $agent->roles()->sync([$agentRole->id]);
    }
}
