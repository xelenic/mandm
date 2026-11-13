<?php

namespace App\Filament\Resources\QrCodes;

use App\Filament\Resources\QrCodes\Pages\ListQrCodes;
use App\Filament\Resources\QrCodes\Tables\QrCodesTable;
use App\Models\QrCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QrCodeResource extends Resource
{
    protected static ?string $model = QrCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Christmas promo';

    protected static ?string $navigationLabel = 'QR codes';

    protected static ?string $modelLabel = 'QR code';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'serial';

    public static function table(Table $table): Table
    {
        return QrCodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQrCodes::route('/'),
        ];
    }
}
