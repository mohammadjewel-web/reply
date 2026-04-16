<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const SLUG_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'grants_admin_panel',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'grants_admin_panel' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /**
     * Keep the system Administrator role in sync with every permission (full access).
     */
    public function syncAdministratorPermissions(): void
    {
        if ($this->slug !== self::SLUG_ADMIN) {
            return;
        }

        $ids = Permission::query()->orderBy('id')->pluck('id')->all();
        if ($ids !== []) {
            $this->permissions()->sync($ids);
        }
    }
}
