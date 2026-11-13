<?php

namespace App\Filament\Resources\QrCodes\Tables;

use App\Enums\Gift;
use App\Models\QrCode;
use App\Services\QrCodeExporter;
use Filament\Actions\Action;
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
            ]);
    }
}
