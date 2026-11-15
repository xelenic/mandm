<?php

namespace App\Actions;

use App\Models\Claim;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Deletes every QR code and every gift claim, so codes can be generated
 * again from scratch.
 *
 * This is irreversible: printed QR codes stop working and winners'
 * details are gone. Callers must get explicit confirmation first.
 */
final class ClearPromoData
{
    /**
     * What would be deleted right now.
     *
     * @return array{qr_codes: int, scanned: int, claims: int}
     */
    public function counts(): array
    {
        return [
            'qr_codes' => QrCode::query()->count(),
            'scanned' => QrCode::query()->where('scan_count', '>', 0)->count(),
            'claims' => Claim::query()->count(),
        ];
    }

    /**
     * @return array{qr_codes: int, claims: int} Number of rows deleted.
     */
    public function handle(?User $by = null, string $via = 'admin panel'): array
    {
        $deleted = DB::transaction(fn (): array => [
            'claims' => Claim::query()->delete(),
            'qr_codes' => QrCode::query()->delete(),
        ]);

        // Keep a record of who wiped the promotion and when.
        Log::warning('Promo data cleared', [
            ...$deleted,
            'by' => $by?->email ?? 'console',
            'via' => $via,
        ]);

        return $deleted;
    }
}
