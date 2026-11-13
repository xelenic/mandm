<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Gift: string implements HasColor, HasLabel
{
    case SpaCeylon = 'spa_ceylon';
    case ShangriLaDinner = 'shangri_la_dinner';

    public function getLabel(): string
    {
        return match ($this) {
            self::SpaCeylon => 'Spa Ceylon Gift Voucher',
            self::ShangriLaDinner => 'Shangri-La Hotel Dinner Voucher',
        };
    }

    /**
     * Short line shown to the winner under the gift name.
     */
    public function tagline(): string
    {
        return match ($this) {
            self::SpaCeylon => 'Treat yourself to a relaxing Spa Ceylon experience.',
            self::ShangriLaDinner => 'Enjoy a festive dinner at the Shangri-La Hotel.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SpaCeylon => 'success',
            self::ShangriLaDinner => 'warning',
        };
    }
}
