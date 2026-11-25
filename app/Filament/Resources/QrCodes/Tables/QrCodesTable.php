<?php

namespace App\Filament\Resources\QrCodes\Tables;

use App\Actions\ResetClaim;
use App\Enums\ClaimStatus;
use App\Enums\Gift;
use App\Models\QrCode;
use App\Services\QrCodeExporter;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QrCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('claim'))
            ->columns([
                TextColumn::make('serial')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->copyable(),
                TextColumn::make('gift')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->state(fn (QrCode $record): string => match (true) {
                        $record->claim !== null => 'Claimed',
                        $record->scan_count > 0 => 'Scanned',
                        default => 'Not scanned',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Claimed' => 'success',
                        'Scanned' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('scan_count')
                    ->label('Scans')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('first_scanned_at')
                    ->label('First scanned')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('last_scanned_at')
                    ->label('Last scanned')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('serial')
            ->filters([
                SelectFilter::make('gift')
                    ->options(Gift::class),
                TernaryFilter::make('scanned')
                    ->queries(
                        true: fn (Builder $query) => $query->where('scan_count', '>', 0),
                        false: fn (Builder $query) => $query->where('scan_count', 0),
                    ),
                TernaryFilter::make('claimed')
                    ->queries(
                        true: fn (Builder $query) => $query->has('claim'),
                        false: fn (Builder $query) => $query->doesntHave('claim'),
                    ),
            ])
            ->recordActions([
                Action::make('showQr')
                    ->label('Show QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('gray')
                    ->modalHeading(fn (QrCode $record): string => "QR code {$record->serial}")
                    ->modalContent(fn (QrCode $record) => view('filament.qr-code-preview', [
                        'url' => $record->url(),
                        'svg' => app(QrCodeExporter::class)->svg($record->url()),
                        'note' => 'Opening this link counts as a scan. Admins never need to claim a gift from here.',
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth(Width::Small),
                self::resetClaimAction(),
            ]);
    }

    /**
     * Undo the claim on this code (e.g. a test claim or a mistake), so it can
     * be claimed again. Only shown for claimed codes; a reason is required.
     */
    private static function resetClaimAction(): Action
    {
        return Action::make('resetClaim')
            ->label('Reset claim')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->visible(fn (QrCode $record): bool => $record->claim !== null)
            ->authorize(fn (): bool => (bool) auth()->user()?->is_admin)
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('danger')
            ->modalHeading(fn (QrCode $record): string => "Reset the claim on {$record->serial}?")
            ->modalDescription(function (QrCode $record): string {
                $claim = $record->claim;

                $message = "Claim {$claim->reference} ({$claim->gift->getLabel()}) by {$claim->name}, "
                    ."claimed on {$claim->created_at->format('j M Y, H:i')}, status: {$claim->status->getLabel()}. "
                    .'Resetting permanently deletes this winner\'s details and clears the scan count, so the QR code is back to "Not scanned" '
                    .'and can be claimed again by whoever scans it next.';

                if (in_array($claim->status, [ClaimStatus::VoucherSent, ClaimStatus::Collected], true)) {
                    $message .= ' WARNING: this winner\'s voucher has already been '
                        .($claim->status === ClaimStatus::Collected ? 'collected' : 'sent')
                        .'. Resetting means the same gift could be claimed twice.';
                }

                return $message;
            })
            ->schema([
                Textarea::make('reason')
                    ->label('Reason')
                    ->placeholder('e.g. Test claim before launch')
                    ->helperText('Recorded in the log with your name.')
                    ->required()
                    ->maxLength(500)
                    ->rows(2),
            ])
            ->modalSubmitActionLabel('Reset claim')
            ->action(function (QrCode $record, array $data): void {
                $claim = app(ResetClaim::class)->handle($record, auth()->user(), $data['reason']);

                Notification::make()
                    ->success()
                    ->title("Claim reset on {$record->serial}")
                    ->body($claim
                        ? "Claim {$claim->reference} was removed and the code is back to \"Not scanned\". It can be claimed again."
                        : 'This QR code had no claim; its scan count was cleared.')
                    ->send();
            });
    }
}
