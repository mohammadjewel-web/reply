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

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()->withCount('roles')->orderBy('name')->paginate(25);

        $stats = [
            'total' => Permission::query()->count(),
            'system' => Permission::query()->where('is_system', true)->count(),
            'custom' => Permission::query()->where('is_system', false)->count(),
            'used_by_roles' => Permission::query()->has('roles')->count(),
        ];

        return view('admin.permissions.index', compact('permissions', 'stats'));
    }

    public function create(): View
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPermission($request);

        Permission::query()->create($validated);

        Role::query()->where('slug', Role::SLUG_ADMIN)->first()?->syncAdministratorPermissions();

        return redirect()->route('permissions.index')->with('status', __('Permission created.'));
    }

    public function edit(Permission $permission): View
    {
        $permission->loadCount('roles');

        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        if ($permission->is_system) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:2000'],
            ]);
            $permission->fill([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ])->save();
        } else {
            $validated = $this->validatedPermission($request, $permission);
            $permission->fill($validated)->save();
        }

        return redirect()->route('permissions.index')->with('status', __('Permission updated.'));
    }

    public function destroy(Request $request, Permission $permission): RedirectResponse
    {
        $isPanelAdmin = (bool) $request->user()?->hasElevatedPanelAccess();

        if (! $isPanelAdmin && $permission->roles()->exists()) {
            return back()->withErrors(['permission' => __('Detach this permission from all roles before deleting it.')]);
        }

        // Force-delete path for panel admins: drop role links first.
        $permission->roles()->detach();
        $permission->delete();

        return redirect()->route('permissions.index')->with('status', __('Permission deleted.'));
    }

    /**
     * @return array{name: string, slug: string, description: string|null, is_system: bool}
     */
    private function validatedPermission(Request $request, ?Permission $ignore = null): array
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
                Rule::unique('permissions', 'slug')->ignore($ignore),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ];
    }
}
