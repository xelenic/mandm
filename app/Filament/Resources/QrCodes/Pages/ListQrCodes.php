<?php

namespace App\Filament\Resources\QrCodes\Pages;

use App\Enums\Gift;
use App\Filament\Resources\QrCodes\QrCodeResource;
use App\Models\QrCode;
use App\Services\Print\QrCardRenderer;
use App\Services\QrCodeExporter;
use App\Services\QrCodeGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use RuntimeException;

class ListQrCodes extends ListRecords
{
    protected static string $resource = QrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->tryAgainQrAction(),
            $this->generateAction(),
            $this->exportAction(),
        ];
    }

    /**
     * The one shared QR code printed on every package without a gift.
     */
    private function tryAgainQrAction(): Action
    {
        return Action::make('tryAgainQr')
            ->label('Try-again QR')
            ->icon(Heroicon::OutlinedQrCode)
            ->color('gray')
            ->modalHeading('Shared "try again" QR code')
            ->modalDescription('Print this same QR code on every package without a gift. It\'s included in the print ZIP as try-again.svg.')
            ->modalContent(fn () => view('filament.qr-code-preview', [
                'url' => QrCode::tryAgainUrl(),
                'svg' => app(QrCodeExporter::class)->svg(QrCode::tryAgainUrl()),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalWidth(Width::Medium);
    }

    /**
     * One-off: creates a unique code for every gift package and assigns the
     * gifts at random. Disappears once codes exist, because printed codes
     * must never change.
     */
    private function generateAction(): Action
    {
        return Action::make('generate')
            ->label('Generate QR codes')
            ->icon(Heroicon::OutlinedSparkles)
            ->visible(fn (): bool => ! QrCode::query()->exists())
            ->modalHeading('Generate gift QR codes')
            ->modalDescription(fn (): string => 'Creates one unique QR code per gift package. '
                .'Packages without a gift all use the same "try again" QR code, so they don\'t need codes. '
                .'This can only be done once: after printing, codes can never be regenerated.')
            ->modalSubmitActionLabel('Generate')
            ->schema([
                TextInput::make(Gift::SpaCeylon->value)
                    ->label(Gift::SpaCeylon->getLabel().'s')
                    ->integer()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100000)
                    ->default(config('promo.gifts.spa_ceylon')),
                TextInput::make(Gift::ShangriLaDinner->value)
                    ->label(Gift::ShangriLaDinner->getLabel().'s')
                    ->integer()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100000)
                    ->default(config('promo.gifts.shangri_la_dinner')),
            ])
            ->action(function (array $data): void {
                try {
                    $count = app(QrCodeGenerator::class)->generate([
                        Gift::SpaCeylon->value => (int) $data[Gift::SpaCeylon->value],
                        Gift::ShangriLaDinner->value => (int) $data[Gift::ShangriLaDinner->value],
                    ]);
                } catch (RuntimeException|InvalidArgumentException $e) {
                    Notification::make()->danger()->title('QR codes were not generated')->body($e->getMessage())->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title("{$count} gift QR codes generated")
                    ->body('Next: use "Export for printing" to download the file for your printer.')
                    ->send();
            });
    }

    private function exportAction(): Action
    {
        return Action::make('export')
            ->label('Export for printing')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->visible(fn (): bool => QrCode::query()->exists())
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedArrowDownTray)
            ->modalHeading('Export QR codes for printing')
            ->modalDescription(fn (): string => self::exportWarning())
            ->modalSubmitActionLabel('Download ZIP')
            ->action(fn () => redirect()->to(
                URL::temporarySignedRoute('admin.qr-codes.export', now()->addMinutes(5))
            ));
    }

    private static function exportWarning(): string
    {
        $baseUrl = rtrim((string) config('promo.qr_base_url'), '/');

        $message = 'You get a ZIP with print-ready cards on your card artwork (one gift card per gift package, '
            .'plus one try-again card for every package without a gift), the bare QR images and a CSV list. '
            .'Gift codes link to '.$baseUrl.'/r/… and the try-again code to '.$baseUrl.'/try-again. '
            .'Check this is your live website address: it can\'t be changed after printing.';

        if (! QrCardRenderer::templateExists()) {
            $message .= ' WARNING: the card artwork PDF was not found ('.config('promo.card.template').'), '
                .'so the ZIP will only contain bare QR images. Check PROMO_CARD_TEMPLATE.';
        }

        $host = parse_url($baseUrl, PHP_URL_HOST) ?: '';

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test')) {
            $message .= ' WARNING: this is a local development address that won\'t work on customers\' phones. '
                .'Set PROMO_QR_BASE_URL in .env before printing.';
        }

        return $message;
    }
}
