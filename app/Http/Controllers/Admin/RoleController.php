<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()->withCount('users')->orderBy('name')->paginate(20);

        $stats = [
            'total' => Role::query()->count(),
            'system' => Role::query()->where('is_system', true)->count(),
            'custom' => Role::query()->where('is_system', false)->count(),
            'panel' => Role::query()->where('grants_admin_panel', true)->count(),
        ];

        return view('admin.roles.index', compact('roles', 'stats'));
    }

    public function create(): View
    {
        $permissions = Permission::query()->orderBy('name')->get();

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedRole($request);
        $permissionIds = $this->validatedPermissionIds($request);

        $role = Role::query()->create($validated);
        $role->permissions()->sync($permissionIds);

        return redirect()->route('roles.index')->with('status', __('Role created.'));
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');
        $permissions = Permission::query()->orderBy('name')->get();

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->is_system) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:2000'],
                'permission_ids' => ['nullable', 'array'],
                'permission_ids.*' => ['integer', 'exists:permissions,id'],
            ]);
            $role->fill([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ])->save();
            $permissionIds = array_values(array_unique(array_map('intval', $validated['permission_ids'] ?? [])));
        } else {
            $validated = $this->validatedRole($request, $role);
            $role->fill($validated)->save();
            $permissionIds = $this->validatedPermissionIds($request);
        }

        if ($role->slug === Role::SLUG_ADMIN) {
            $role->syncAdministratorPermissions();
        } else {
            $role->permissions()->sync($permissionIds);
        }

        return redirect()->route('roles.index')->with('status', __('Role updated.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => __('System roles cannot be deleted.')]);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => __('Remove this role from all users before deleting it.')]);
        }

        $role->delete();

        return redirect()->route('roles.index')->with('status', __('Role deleted.'));
    }

    /**
     * @return array{name: string, slug: string, description: string|null, grants_admin_panel: bool, is_system: bool}
     */
    private function validatedRole(Request $request, ?Role $ignore = null): array
    {
        $rawSlug = $request->input('slug');
        $slug = (is_string($rawSlug) && trim($rawSlug) !== '')
            ? Str::slug(trim($rawSlug))
            : Str::slug((string) $request->input('name'));
        $request->merge(['slug' => $slug]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('roles', 'slug')->ignore($ignore),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'grants_admin_panel' => ['sometimes', 'boolean'],
        ]);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'grants_admin_panel' => $request->boolean('grants_admin_panel'),
            'is_system' => false,
        ];
    }

    /**
     * @return list<int>
     */
    private function validatedPermissionIds(Request $request): array
    {
        $validated = $request->validate([
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $ids = $validated['permission_ids'] ?? [];

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
