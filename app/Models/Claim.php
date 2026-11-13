<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Enums\Gift;
use Database\Factories\ClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A winner's request for their gift, submitted from the QR claim form.
 *
 * Only the winner's own details are fillable; the QR code, gift, reference
 * and status are always set explicitly by the code that creates the claim.
 *
 * @property Gift $gift
 * @property ClaimStatus $status
 */
#[Fillable(['name', 'phone', 'email', 'nic'])]
class Claim extends Model
{
    /** @use HasFactory<ClaimFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'gift' => Gift::class,
            'status' => ClaimStatus::class,
        ];
    }

    /**
     * The stored +947XXXXXXXX number, spaced for reading: +94 77 123 4567.
     */
    public function formattedPhone(): string
    {
        return preg_replace('/^\+94(\d{2})(\d{3})(\d{4})$/', '+94 $1 $2 $3', $this->phone) ?? $this->phone;
    }

    /**
     * @return BelongsTo<QrCode, $this>
     */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
