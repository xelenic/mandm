<?php

namespace App\Filament\Resources\Claims\Tables;

use App\Enums\ClaimStatus;
use App\Enums\Gift;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->copyable(),
                TextColumn::make('gift')
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('nic')
                    ->label('NIC')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('qrCode.serial')
                    ->label('QR code')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Claimed at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ClaimStatus::class),
                SelectFilter::make('gift')
                    ->options(Gift::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
