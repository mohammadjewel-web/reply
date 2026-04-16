<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_employees_create(): void
    {
        $this->get(route('employees.create'))->assertRedirect();
    }

    public function test_authorized_user_can_view_create_employee_form(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => true,
        ]);

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);

        $this->actingAs($admin)->get(route('employees.create'))->assertOk();
    }

    public function test_authorized_user_can_create_employee(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => true,
        ]);

        $agentRole = Role::query()->create([
            'name' => 'Agent',
            'slug' => 'agent-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => false,
        ]);

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->post(route('employees.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role_ids' => [$agentRole->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'name' => 'Jane Doe']);
        $jane = User::query()->where('email', 'jane@example.com')->first();
        $this->assertNotNull($jane);
        $this->assertTrue($jane->roles->contains($agentRole));
    }

    public function test_authorized_user_can_view_edit_employee_form(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => true,
        ]);

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);

        $this->actingAs($admin)->get(route('employees.edit', $admin))->assertOk();
    }

    public function test_roles_only_patch_updates_roles_without_touching_profile(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => true,
        ]);

        $agentRole = Role::query()->create([
            'name' => 'Agent',
            'slug' => 'agent-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => false,
        ]);

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);

        $other = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'job_title' => 'Boss',
        ]);
        $other->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->patch(route('employees.update', $other), [
            'roles_only' => '1',
            'role_ids' => [$adminRole->id, $agentRole->id],
        ]);

        $response->assertRedirect();
        $other->refresh();
        $this->assertTrue($other->roles->contains($agentRole));
        $this->assertSame('Boss', $other->job_title);
    }

    public function test_panel_admin_cannot_change_own_roles(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => true,
        ]);

        $agentRole = Role::query()->create([
            'name' => 'Agent',
            'slug' => 'agent-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => false,
        ]);

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->patch(route('employees.update', $admin), [
            'roles_only' => '1',
            'role_ids' => [$agentRole->id],
        ]);

        $response->assertSessionHasErrors('role_ids');
        $admin->refresh();
        $this->assertTrue($admin->roles->contains($adminRole));
        $this->assertFalse($admin->roles->contains($agentRole));
    }

    public function test_admin_can_delete_employee_without_panel_when_another_admin_exists(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => true,
        ]);

        $agentRole = Role::query()->create([
            'name' => 'Agent',
            'slug' => 'agent-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => false,
        ]);

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);

        $agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $agent->roles()->attach($agentRole);

        $response = $this->actingAs($admin)->delete(route('employees.destroy', $agent));

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseMissing('users', ['id' => $agent->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => true,
        ]);

        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->delete(route('employees.destroy', $admin));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHasErrors('employee');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_with_only_employees_permission_cannot_delete_sole_panel_admin(): void
    {
        $employeesPerm = Permission::query()->where('slug', 'employees.manage')->firstOrFail();

        $hrRole = Role::query()->create([
            'name' => 'HR',
            'slug' => 'hr-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => false,
        ]);
        $hrRole->permissions()->sync([$employeesPerm->id]);

        $panelRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin-test',
            'description' => null,
            'is_system' => false,
            'grants_admin_panel' => true,
        ]);

        $hr = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $hr->roles()->attach($hrRole);

        $solePanelUser = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $solePanelUser->roles()->attach($panelRole);

        $response = $this->actingAs($hr)->delete(route('employees.destroy', $solePanelUser));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHasErrors('employee');
        $this->assertDatabaseHas('users', ['id' => $solePanelUser->id]);
    }
}
