<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ChannelMessage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    protected function profileFieldRules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * System administrators (roles with admin panel access) cannot change their own role assignment.
     */
    protected function ensureCanChangeTargetRoles(Request $request, User $target, array $newIds): ?RedirectResponse
    {
        if ($target->id !== $request->user()->id || ! $request->user()->hasElevatedPanelAccess()) {
            return null;
        }

        $target->loadMissing('roles');
        $current = $target->roles->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $submitted = collect($newIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($current !== $submitted) {
            return back()->withErrors([
                'role_ids' => __('You cannot change your own roles. Ask another administrator.'),
            ])->withInput();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{phone: ?string, job_title: ?string, department: ?string, notes: ?string}
     */
    protected function normalizedProfileFields(array $validated): array
    {
        $blank = static fn (?string $v): ?string => ($v === null || trim($v) === '') ? null : trim($v);

        return [
            'phone' => $blank($validated['phone'] ?? null),
            'job_title' => $blank($validated['job_title'] ?? null),
            'department' => $blank($validated['department'] ?? null),
            'notes' => $blank($validated['notes'] ?? null),
        ];
    }

    public function index(): View
    {
        $users = User::query()->with('roles')->orderBy('name')->paginate(15);
        $allRoles = Role::query()->orderBy('name')->get();

        $totalEmployees = User::query()->count();
        $activeEmployees = User::query()->where('is_active', true)->count();

        $canDeleteById = $users->getCollection()
            ->mapWithKeys(fn (User $u) => [$u->id => $this->canDeleteEmployee($u)])
            ->all();

        return view('admin.employees.index', [
            'users' => $users,
            'allRoles' => $allRoles,
            'canDeleteById' => $canDeleteById,
            'stats' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
                'inactive' => max(0, $totalEmployees - $activeEmployees),
                'verified_emails' => User::query()->whereNotNull('email_verified_at')->count(),
                'pending_email' => User::query()->whereNull('email_verified_at')->count(),
            ],
            'rolesCount' => Role::query()->count(),
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));

        $usersQuery = User::query()->with('roles')->orderBy('name');
        if ($term !== '') {
            $usersQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('job_title', 'like', "%{$term}%")
                    ->orWhere('department', 'like', "%{$term}%");
            });
        }

        $users = $usersQuery->paginate(15)->appends(['q' => $term]);
        $canDeleteById = $users->getCollection()
            ->mapWithKeys(fn (User $u) => [$u->id => $this->canDeleteEmployee($u)])
            ->all();

        $html = view('admin.employees.partials.table', [
            'users' => $users,
            'allRoles' => Role::query()->orderBy('name')->get(),
            'canDeleteById' => $canDeleteById,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function create(): View
    {
        $allRoles = Role::query()->orderBy('name')->get();

        return view('admin.employees.create', [
            'allRoles' => $allRoles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'is_active' => ['required', 'in:0,1'],
        ], $this->profileFieldRules()));

        $newIds = array_values(array_unique(array_map('intval', $validated['role_ids'])));
        $willHavePanel = Role::query()->whereIn('id', $newIds)->where('grants_admin_panel', true)->exists();

        $othersStillHavePanel = User::query()
            ->whereHas('roles', fn ($q) => $q->where('grants_admin_panel', true))
            ->exists();

        if (! $willHavePanel && ! $othersStillHavePanel) {
            return back()->withErrors([
                'role_ids' => __('At least one user must keep admin panel access.'),
            ])->withInput();
        }

        $profile = $this->normalizedProfileFields($validated);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => (bool) (int) $validated['is_active'],
            ...$profile,
        ]);

        $user->roles()->sync($newIds);

        AppSetting::current()->applyMailConfig();

        try {
            $user->sendEmailVerificationNotification();

            return redirect()->route('employees.index')->with('status', __('Employee created. Verification email sent.'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('employees.index')->with('status', __('Employee created. Email could not be sent. Check email settings.'));
        }
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('admin.employees.edit', [
            'employee' => $user,
            'allRoles' => Role::query()->orderBy('name')->get(),
            'canDeleteEmployee' => $this->canDeleteEmployee($user),
        ]);
    }

    public function profile(User $user): Response
    {
        $user->loadMissing('roles');

        $messagesQuery = ChannelMessage::query()
            ->where('user_id', $user->id)
            ->where('direction', ChannelMessage::DIRECTION_OUTBOUND);

        $totalMessagesSent = (clone $messagesQuery)->count();

        $messages = (clone $messagesQuery)
            ->with(['conversation.channelAccount:id,name,type,is_active'])
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        return response()
            ->view('admin.employees.profile', [
                'employee' => $user,
                'totalMessagesSent' => $totalMessagesSent,
                'messages' => $messages,
            ])
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function sendVerification(User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('employees.index')->with('status', __('This email is already verified.'));
        }

        AppSetting::current()->applyMailConfig();

        try {
            $user->sendEmailVerificationNotification();

            return redirect()->route('employees.index')->with('status', __('Verification email sent to :email.', ['email' => $user->email]));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('employees.index')->withErrors([
                'employee' => __('Could not send verification email. Check email settings and try again.'),
            ]);
        }
    }

    /**
     * Another user may be deleted unless they are the only remaining admin-panel user.
     */
    protected function canDeleteEmployee(User $target): bool
    {
        $actor = auth()->user();
        if (! $actor || $actor->id === $target->id) {
            return false;
        }

        if (! $target->roles()->where('grants_admin_panel', true)->exists()) {
            return true;
        }

        return User::query()
            ->where('id', '!=', $target->id)
            ->whereHas('roles', fn ($q) => $q->where('grants_admin_panel', true))
            ->exists();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('employees.index')->withErrors([
                'employee' => __('You cannot delete your own account.'),
            ]);
        }

        if (! $this->canDeleteEmployee($user)) {
            return redirect()->route('employees.index')->withErrors([
                'employee' => __('At least one administrator must remain. Assign admin panel access to another user before deleting this account.'),
            ]);
        }

        $user->delete();

        return redirect()->route('employees.index')->with('status', __('Employee removed.'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($request->boolean('roles_only')) {
            return $this->updateRolesOnly($request, $user);
        }

        return $this->updateFull($request, $user);
    }

    protected function updateRolesOnly(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $newIds = array_values(array_unique(array_map('intval', $validated['role_ids'])));

        if ($response = $this->ensureCanChangeTargetRoles($request, $user, $newIds)) {
            return $response;
        }

        $willHavePanel = Role::query()->whereIn('id', $newIds)->where('grants_admin_panel', true)->exists();

        $othersStillHavePanel = User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('roles', fn ($q) => $q->where('grants_admin_panel', true))
            ->exists();

        if (! $willHavePanel && ! $othersStillHavePanel) {
            $message = $user->id === $request->user()->id
                ? __('You cannot remove the last admin panel access.')
                : __('At least one user must keep admin panel access.');

            return back()->withErrors(['role_ids' => $message])->withInput();
        }

        $user->roles()->sync($newIds);

        return back()->with('status', __('Roles updated.'));
    }

    protected function updateFull(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'is_active' => ['required', 'in:0,1'],
        ], $this->profileFieldRules()));

        $makeActive = (bool) (int) $validated['is_active'];

        if ($user->id === $request->user()->id && ! $makeActive) {
            return back()->withErrors([
                'is_active' => __('You cannot deactivate your own account.'),
            ])->withInput();
        }

        $newIds = array_values(array_unique(array_map('intval', $validated['role_ids'])));

        if ($response = $this->ensureCanChangeTargetRoles($request, $user, $newIds)) {
            return $response;
        }

        $willHavePanel = Role::query()->whereIn('id', $newIds)->where('grants_admin_panel', true)->exists();

        $othersStillHavePanel = User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('roles', fn ($q) => $q->where('grants_admin_panel', true))
            ->exists();

        if (! $willHavePanel && ! $othersStillHavePanel) {
            $message = $user->id === $request->user()->id
                ? __('You cannot remove the last admin panel access.')
                : __('At least one user must keep admin panel access.');

            return back()->withErrors(['role_ids' => $message])->withInput();
        }

        $profile = $this->normalizedProfileFields($validated);

        $emailChanged = $user->email !== $validated['email'];

        $user->forceFill(array_merge(
            [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $makeActive,
                'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
            ],
            $profile
        ))->save();

        $user->roles()->sync($newIds);

        return redirect()->route('employees.edit', $user)->with('status', __('Employee updated.'));
    }
}
