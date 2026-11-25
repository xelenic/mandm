<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Gift: string implements HasColor, HasLabel
{
    case SpaCeylon = 'spa_ceylon';
    case CinnamonLifeDinner = 'cinnamon_life_dinner';

    public function getLabel(): string
    {
        return match ($this) {
            self::SpaCeylon => 'Spa Ceylon Gift Voucher',
            self::CinnamonLifeDinner => 'Cinnamon Life Dinner Voucher',
        };
    }

    /**
     * The prize as winners see it, from the lucky draw design.
     */
    public function prizeTitle(): string
    {
        return match ($this) {
            self::SpaCeylon => 'Rs. 3,000 Spa Ceylon Gift Certificate',
            self::CinnamonLifeDinner => 'Rs. 10,300 Cinnamon Life Gift Voucher',
        };
    }

    /**
     * The "Congratulations!" card in public/images/lucky-draw (without ".webp").
     */
    public function prizeImage(): string
    {
        return match ($this) {
            self::SpaCeylon => 'prize-spa-ceylon',
            self::CinnamonLifeDinner => 'prize-cinnamon-life',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SpaCeylon => 'success',
            self::CinnamonLifeDinner => 'warning',
        };
    }
}
