<?php

namespace App\Actions;

use App\Models\Claim;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Undoes the claim on one QR code (e.g. a test claim or a claim made by
 * mistake), so the code is back to new: no claim and "Not scanned".
 *
 * This permanently deletes the winner's details. The log keeps a record of
 * who reset which claim and why (without the winner's personal details).
 */
final class ResetClaim
{
    /**
     * @return Claim|null The claim that was removed, or null if the code had none.
     */
    public function handle(QrCode $qrCode, ?User $by, string $reason): ?Claim
    {
        $scansBefore = (int) $qrCode->scan_count;

        // Both together or neither: delete the claim and clear the scan history.
        $claim = DB::transaction(function () use ($qrCode): ?Claim {
            $locked = QrCode::query()->whereKey($qrCode->getKey())->lockForUpdate()->firstOrFail();
            $claim = Claim::query()->where('qr_code_id', $locked->getKey())->first();
            $claim?->delete();

            $locked->forceFill([
                'scan_count' => 0,
                'first_scanned_at' => null,
                'last_scanned_at' => null,
            ])->save();

            return $claim;
        });

        // Keep the model the caller holds in step with the database.
        $qrCode->forceFill(['scan_count' => 0, 'first_scanned_at' => null, 'last_scanned_at' => null])->syncOriginal();
        $qrCode->unsetRelation('claim');

        if ($claim !== null) {
            Log::warning('Gift claim reset', [
                'qr_serial' => $qrCode->serial,
                'reference' => $claim->reference,
                'gift' => $claim->gift->value,
                'status' => $claim->status->value,
                'claimed_at' => $claim->created_at?->toIso8601String(),
                'scans_before_reset' => $scansBefore,
                'by' => $by?->email,
                'reason' => $reason,
            ]);
        }

        return $claim;
    }
}
