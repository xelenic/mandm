<?php

namespace App\Actions;

use App\Enums\ClaimStatus;
use App\Exceptions\GiftAlreadyClaimed;
use App\Models\Claim;
use App\Models\QrCode;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Random\Engine\Secure;
use Random\Randomizer;

/**
 * Records a winner's claim for the gift attached to a QR code.
 *
 * Safe against double claims: the QR code row is locked inside a
 * transaction, and the unique index on claims.qr_code_id is the final
 * guarantee if two people submit at the same moment.
 */
class ClaimGift
{
    private const REFERENCE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     * @param  array{name: string, phone: string, email: string, nic: string}  $details
     *
     * @throws GiftAlreadyClaimed
     */
    public function handle(QrCode $qrCode, array $details, ?string $ipAddress = null, ?string $userAgent = null): Claim
    {
        try {
            return DB::transaction(function () use ($qrCode, $details, $ipAddress, $userAgent): Claim {
                $qrCode = QrCode::query()->whereKey($qrCode->getKey())->lockForUpdate()->firstOrFail();

                if ($qrCode->claim()->exists()) {
                    throw new GiftAlreadyClaimed;
                }

                $claim = new Claim($details);
                $claim->forceFill([
                    'qr_code_id' => $qrCode->getKey(),
                    'gift' => $qrCode->gift,
                    'reference' => $this->newReference(),
                    'status' => ClaimStatus::Pending,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ])->save();

                return $claim;
            });
        } catch (UniqueConstraintViolationException) {
            throw new GiftAlreadyClaimed;
        }
    }

    private function newReference(): string
    {
        $randomizer = new Randomizer(new Secure);

        do {
            $reference = 'XMAS-'.$randomizer->getBytesFromString(self::REFERENCE_ALPHABET, 8);
        } while (Claim::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
