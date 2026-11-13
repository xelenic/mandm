<?php

namespace App\Policies;

use App\Models\QrCode;
use App\Models\User;

/**
 * QR codes are printed, so they can be viewed but never created, edited
 * or deleted one by one. The whole set is created once by QrCodeGenerator.
 */
class QrCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, QrCode $qrCode): bool
    {
        return $user->is_admin;
    }
}
