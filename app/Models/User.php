<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'job_title',
        'department',
        'notes',
        'password',
        'is_active',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Users who should receive inbox-related notifications (inbox permission or full admin panel).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInboxNotifiable(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereHas('roles', fn (Builder $r) => $r->where('grants_admin_panel', true))
                ->orWhereHas('roles.permissions', fn (Builder $p) => $p->where('permissions.slug', 'inbox.access'));
        });
    }

    /**
     * @return array{mute_sound: bool, mute_desktop: bool}
     */
    public function notificationPreferencesResolved(): array
    {
        $p = $this->notification_preferences ?? [];

        return [
            'mute_sound' => (bool) ($p['mute_sound'] ?? false),
            'mute_desktop' => (bool) ($p['mute_desktop'] ?? false),
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function assignedConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_to_user_id');
    }

    public function isUserActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function hasRole(string $slug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $role) => $role->slug === $slug);
        }

        return $this->roles()->where('slug', $slug)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->canAccessAdminPanel();
    }

    /**
     * Roles with the "admin panel" flag bypass permission checks (full access).
     */
    public function hasElevatedPanelAccess(): bool
    {
        if (! $this->relationLoaded('roles')) {
            return $this->roles()->where('grants_admin_panel', true)->exists();
        }

        return $this->roles->contains(fn (Role $role) => $role->grants_admin_panel);
    }

    /**
     * At least one role grants the legacy admin-panel flag (used for employee safeguards).
     */
    public function canAccessAdminPanel(): bool
    {
        return $this->hasElevatedPanelAccess();
    }

    public function hasPermission(string $slug): bool
    {
        return $this->roles()->whereHas('permissions', function ($q) use ($slug) {
            $q->where('permissions.slug', $slug);
        })->exists();
    }

    /**
     * Whether the user may perform an action guarded by the given permission slug.
     */
    public function allows(string $permissionSlug): bool
    {
        if ($this->hasElevatedPanelAccess()) {
            return true;
        }

        return $this->hasPermission($permissionSlug);
    }

    public function canSeeWorkbench(): bool
    {
        if ($this->hasElevatedPanelAccess()) {
            return true;
        }

        return $this->roles()->whereHas('permissions')->exists();
    }
}
