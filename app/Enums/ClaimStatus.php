<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a winner's gift is in the hand-over process. Updated by the team
 * in the admin panel.
 */
enum ClaimStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case VoucherSent = 'voucher_sent';
    case Collected = 'collected';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::VoucherSent => 'Voucher sent',
            self::Collected => 'Collected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::VoucherSent => 'info',
            self::Collected => 'success',
            self::Cancelled => 'danger',
        };
    }
}
