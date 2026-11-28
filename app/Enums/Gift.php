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
        return $this->prizeAmount().' '.$this->prizeName();
    }

    /** The voucher's value, e.g. "Rs. 3,000". */
    public function prizeAmount(): string
    {
        return match ($this) {
            self::SpaCeylon => 'Rs. 3,000',
            self::CinnamonLifeDinner => 'Rs. 10,300',
        };
    }

    /** The voucher's name, e.g. "Spa Ceylon Gift Certificate". */
    public function prizeName(): string
    {
        return match ($this) {
            self::SpaCeylon => 'Spa Ceylon Gift Certificate',
            self::CinnamonLifeDinner => 'Cinnamon Life Gift Voucher',
        };
    }

    /**
     * The voucher card shown to winners, in public/images/lucky-draw (without ".webp").
     */
    public function prizeImage(): string
    {
        return match ($this) {
            self::SpaCeylon => 'voucher-spa-ceylon',
            self::CinnamonLifeDinner => 'voucher-cinnamon-life',
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
