<?php

namespace App\Policies;

use App\Models\User;

/**
 * Who may manage user accounts. Filament applies this to every Users
 * screen and action, including each record in a bulk delete.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, User $model): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, User $model): bool
    {
        return $user->is_admin;
    }

    /**
     * Admins may delete other users, never themselves, so the panel
     * can't be left without anyone able to sign in.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->is_admin && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_admin;
    }
}
