<?php

namespace App\Observers;

use App\Models\Conversation;
use App\Models\User;

class UserObserver
{
    public function updated(User $user): void
    {
        if ($user->wasChanged('is_active') && ! $user->is_active) {
            Conversation::query()
                ->where('assigned_to_user_id', $user->id)
                ->update(['assigned_to_user_id' => null]);
        }
    }

    public function deleting(User $user): void
    {
        Conversation::query()
            ->where('assigned_to_user_id', $user->id)
            ->update(['assigned_to_user_id' => null]);
    }
}
