<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\User;

/**
 * Claims come only from winners scanning their QR code. Admins can read
 * them and update the hand-over status, but not create or delete them, so
 * there is always a full record of every gift given out.
 */
class ClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Claim $claim): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Claim $claim): bool
    {
        return $user->is_admin;
    }
}
