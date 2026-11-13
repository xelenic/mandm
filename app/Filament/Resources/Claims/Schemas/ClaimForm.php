<?php

namespace App\Filament\Resources\Claims\Schemas;

use App\Enums\ClaimStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;

class ClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Hand-over')
                    ->description('Update this as the winner is contacted and receives their voucher.')
                    ->schema([
                        Select::make('status')
                            ->options(ClaimStatus::class)
                            ->required(),
                        Textarea::make('notes')
                            ->rows(4)
                            ->placeholder('e.g. Called on 20 Dec, voucher couriered to Colombo 05'),
                    ]),
                Section::make('Gift')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reference')
                            ->fontFamily(FontFamily::Mono)
                            ->copyable(),
                        TextEntry::make('gift')
                            ->badge(),
                        TextEntry::make('qrCode.serial')
                            ->label('QR code'),
                        TextEntry::make('created_at')
                            ->label('Claimed at')
                            ->dateTime(),
                    ]),
                Section::make('Winner')
                    ->description('As entered by the winner. Correct any typos here.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nic')
                            ->label('NIC')
                            ->required()
                            ->maxLength(12),
                    ]),
            ]);
    }
}
