<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Services\QrCodeExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * File downloads started from the admin panel. Links are signed and
 * short-lived, and the visitor must be a signed-in admin.
 */
class PromoExportController extends Controller
{
    public function qrCodes(Request $request, QrCodeExporter $exporter): BinaryFileResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        // Building the print file (400 cards on the 14 MB artwork + QR images) takes
        // ~10s and ~110 MB here; allow headroom on slower or tighter servers.
        set_time_limit(180);
        ini_set('memory_limit', '512M');

        return response()
            ->download($exporter->toZip(), 'qr-codes-for-print.zip')
            ->deleteFileAfterSend();
    }

    public function claims(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['reference', 'gift', 'status', 'name', 'phone', 'email', 'nic', 'qr_serial', 'claimed_at', 'notes'], escape: '');

            Claim::query()->with('qrCode')->lazyById(500)->each(function (Claim $claim) use ($out): void {
                fputcsv($out, [
                    $claim->reference,
                    $claim->gift->getLabel(),
                    $claim->status->getLabel(),
                    self::safeCell($claim->name),
                    $claim->phone,
                    self::safeCell($claim->email),
                    $claim->nic,
                    $claim->qrCode->serial,
                    $claim->created_at->toDateTimeString(),
                    self::safeCell((string) $claim->notes),
                ], escape: '');
            });

            fclose($out);
        }, 'gift-claims-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Winners type their own name and email, so stop Excel from treating
     * a value like "=HYPERLINK(...)" as a formula. (Phone and NIC are
     * already validated and normalised, so they're written as-is.)
     */
    private static function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
