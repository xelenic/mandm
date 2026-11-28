<?php

namespace App\Support;

/**
 * Addresses of the lucky draw images in public/images/lucky-draw.
 *
 * Each address carries a version taken from the file's modification time,
 * so when an image is replaced under the same name (e.g. a new logo),
 * browsers and CDNs fetch the new one instead of showing a cached copy.
 */
final class LuckyDrawImage
{
    /**
     * @param  string  $name  File name without ".webp", e.g. "logo" or "crew".
     */
    public static function url(string $name): string
    {
        $file = "images/lucky-draw/{$name}.webp";
        $path = public_path($file);

        return asset($file).(is_file($path) ? '?v='.filemtime($path) : '');
    }
}
