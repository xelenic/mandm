<?php

namespace App\Services\Print;

use InvalidArgumentException;

final class HexColor
{
    /**
     * "#461100" (or "461100") as [red, green, blue], each 0–255.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public static function toRgb(string $hex): array
    {
        if (preg_match('/^#?([0-9a-f]{6})$/i', trim($hex), $match) !== 1) {
            throw new InvalidArgumentException("Invalid colour [{$hex}]. Use a hex colour like #461100.");
        }

        return array_map(hexdec(...), str_split($match[1], 2));
    }
}
