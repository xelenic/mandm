<?php

namespace App\Filament\Widgets;

use App\Enums\ClaimStatus;
use App\Enums\Gift;
use App\Models\Claim;
use App\Models\QrCode;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PromoStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Christmas promo';

    protected function getStats(): array
    {
        $total = QrCode::query()->count();

        if ($total === 0) {
            return [
                Stat::make('QR codes', 'Not generated yet')
                    ->description('Go to QR codes → Generate QR codes')
                    ->icon(Heroicon::OutlinedQrCode),
            ];
        }

        $scanned = QrCode::query()->where('scan_count', '>', 0)->count();
        $codesByGift = QrCode::query()->toBase()->selectRaw('gift, count(*) as aggregate')->groupBy('gift')->pluck('aggregate', 'gift');
        $claimsByGift = Claim::query()->toBase()->selectRaw('gift, count(*) as aggregate')->groupBy('gift')->pluck('aggregate', 'gift');

        $stats = [
            Stat::make('Gift QR codes scanned', number_format($scanned).' / '.number_format($total))
                ->description(round($scanned / $total * 100).'% of gift packages found')
                ->icon(Heroicon::OutlinedQrCode),
        ];

        foreach (Gift::cases() as $gift) {
            $available = (int) ($codesByGift[$gift->value] ?? 0);
            $claimed = (int) ($claimsByGift[$gift->value] ?? 0);

            $stats[] = Stat::make($gift->getLabel().'s claimed', "{$claimed} / {$available}")
                ->description(($available - $claimed).' not claimed yet')
                ->color($gift->getColor())
                ->icon(Heroicon::OutlinedGift);
        }

        $stats[] = Stat::make('Waiting for hand-over', Claim::query()->where('status', ClaimStatus::Pending)->count())
            ->description('Winners to contact')
            ->color('warning')
            ->icon(Heroicon::OutlinedInboxArrowDown);

        return $stats;
    }
}
