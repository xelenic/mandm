<?php

namespace App\Models;

use App\Enums\Gift;
use Database\Factories\QrCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One unique QR code, printed inside one gift package.
 *
 * Only gift packages get a unique code. Every package without a gift
 * carries the same shared "try again" QR code (see tryAgainUrl()), so
 * those are never stored.
 *
 * Codes are created only by App\Services\QrCodeGenerator and never edited
 * afterwards, because they are already printed.
 *
 * @property Gift $gift
 */
class QrCode extends Model
{
    /** @use HasFactory<QrCodeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'gift' => Gift::class,
            'scan_count' => 'integer',
            'first_scanned_at' => 'datetime',
            'last_scanned_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<Claim, $this>
     */
    public function claim(): HasOne
    {
        return $this->hasOne(Claim::class);
    }

    /**
     * The address encoded in this gift package's QR code.
     */
    public function url(): string
    {
        return self::baseUrl().'/r/'.$this->code;
    }

    /**
     * The address encoded in the single shared QR code printed on every
     * package without a gift.
     */
    public static function tryAgainUrl(): string
    {
        return self::baseUrl().'/try-again';
    }

    public function recordScan(): void
    {
        $now = now();

        $this->forceFill([
            'scan_count' => $this->scan_count + 1,
            'first_scanned_at' => $this->first_scanned_at ?? $now,
            'last_scanned_at' => $now,
        ])->save();
    }

    private static function baseUrl(): string
    {
        return rtrim((string) config('promo.qr_base_url'), '/');
    }
}
