<?php

namespace App\Services;

use App\Enums\Gift;
use App\Models\QrCode;
use App\Services\Print\HexColor;
use App\Services\Print\QrCardRenderer;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Builds the file sent to the printer: a ZIP containing
 * - cards/<gift>-cards.pdf    print-ready cards on the designer's artwork, one PDF per gift
 *                             (e.g. cards/spa-ceylon-gift-voucher-cards.pdf), one card per package,
 * - cards/try-again-card.pdf  the single card for every package without a gift,
 * - svg/MM-0001.svg …         the bare QR images, for printers who build cards themselves,
 * - codes.csv                 the gift codes as a table,
 * - try-again.svg             the bare shared "try again" QR image,
 * - README.txt                printing instructions.
 *
 * Only the card PDFs (split by gift, as the client asked) show which gift
 * a code wins; codes.csv and the SVG images say nothing about gifts.
 */
class QrCodeExporter
{
    public const TRY_AGAIN_FILE = 'try-again.svg';

    public const TRY_AGAIN_CARD_FILE = 'cards/try-again-card.pdf';

    /**
     * Name in the ZIP of one gift's card PDF, e.g. "cards/spa-ceylon-gift-voucher-cards.pdf".
     */
    public static function giftCardsFile(Gift $gift): string
    {
        return 'cards/'.Str::slug($gift->getLabel()).'-cards.pdf';
    }

    public function __construct(private readonly QrCardRenderer $cards) {}

    /**
     * @return string Path to a temporary ZIP file. The caller deletes it.
     */
    public function toZip(): string
    {
        $path = storage_path('app/private/qr-export-'.bin2hex(random_bytes(8)).'.zip');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the export file.');
        }

        try {
            $csv = fopen('php://temp', 'r+');
            fputcsv($csv, ['serial', 'code', 'url', 'svg_file', 'card_text'], escape: '');

            $writer = $this->writer();

            QrCode::query()->lazyById(500)->each(function (QrCode $qrCode) use ($zip, $csv, $writer): void {
                $file = "svg/{$qrCode->serial}.svg";

                fputcsv($csv, [$qrCode->serial, $qrCode->code, $qrCode->url(), $file, (string) $qrCode->cardText()], escape: '');
                $zip->addFromString($file, $writer->writeString($qrCode->url(), ecLevel: ErrorCorrectionLevel::Q()));
            });

            rewind($csv);
            $zip->addFromString('codes.csv', stream_get_contents($csv));
            fclose($csv);

            $zip->addFromString(self::TRY_AGAIN_FILE, $writer->writeString(QrCode::tryAgainUrl(), ecLevel: ErrorCorrectionLevel::Q()));

            $cardFiles = QrCardRenderer::templateExists() ? $this->renderCards() : [];

            foreach ($cardFiles as $name => $file) {
                $zip->addFile($file, $name);
            }

            $zip->addFromString('README.txt', $this->readme($cardFiles !== []));
            $zip->close();
        } catch (Throwable $e) {
            // Don't leave a half-written ZIP behind.
            try {
                $zip->close();
            } catch (Throwable) {
                // Already closed or unwritable; the file check below is what matters.
            }

            if (is_file($path)) {
                unlink($path);
            }

            throw $e;
        }

        // ZipArchive reads added files on close(), so only delete them now.
        array_map(unlink(...), $cardFiles);

        return $path;
    }

    /**
     * @return array<string, string> Name in the ZIP => temporary PDF path.
     */
    private function renderCards(): array
    {
        $files = [];

        try {
            foreach ($this->giftCounts() as $value => $count) {
                $gift = Gift::from($value);

                $cards = (function () use ($gift) {
                    // In serial order, like codes.csv.
                    foreach (QrCode::query()->where('gift', $gift)->lazyById(500) as $qrCode) {
                        yield ['url' => $qrCode->url(), 'text' => $qrCode->cardText()];
                    }
                })();

                $files[self::giftCardsFile($gift)] = $this->cards->render($cards, $gift->getLabel().' cards');
            }

            $files[self::TRY_AGAIN_CARD_FILE] = $this->cards->render(
                [['url' => QrCode::tryAgainUrl(), 'text' => QrCode::tryAgainCardText()]],
                'Try again card',
            );
        } catch (Throwable $e) {
            array_map(unlink(...), $files); // don't leave half an export behind

            throw $e;
        }

        return $files;
    }

    /**
     * Number of codes per gift, for gifts that have any, in the order of the Gift enum.
     *
     * @return array<string, int> Gift value => count.
     */
    private function giftCounts(): array
    {
        $counts = QrCode::query()->toBase()->selectRaw('gift, count(*) as aggregate')->groupBy('gift')->pluck('aggregate', 'gift');

        return collect(Gift::cases())
            ->mapWithKeys(fn (Gift $gift): array => [$gift->value => (int) ($counts[$gift->value] ?? 0)])
            ->filter()
            ->all();
    }

    /**
     * SVG markup for a QR code pointing to $url, ready to inline into HTML.
     */
    public function svg(string $url, int $size = 256): string
    {
        $svg = $this->writer($size)->writeString($url, ecLevel: ErrorCorrectionLevel::Q());

        // Drop the XML declaration so the SVG can be embedded in HTML.
        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }

    private function writer(int $size = 512): Writer
    {
        // Same colour as the printed cards, on white.
        $fill = Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(...HexColor::toRgb((string) config('promo.qr_color'))));

        // Margin 4 = the quiet zone the QR spec requires for reliable scanning.
        return new Writer(new ImageRenderer(new RendererStyle($size, 4, fill: $fill), new SvgImageBackEnd));
    }

    private function readme(bool $withCards): string
    {
        $count = QrCode::query()->count();
        $baseUrl = rtrim((string) config('promo.qr_base_url'), '/');
        $tryAgain = self::TRY_AGAIN_FILE;
        $tryAgainCard = self::TRY_AGAIN_CARD_FILE;
        $color = strtoupper((string) config('promo.qr_color'));
        $tryAgainText = QrCode::tryAgainCardText() === null
            ? ''
            : "\n     It shows the code ".QrCode::tryAgainCardText().' at the bottom right (the same on every copy).';

        $giftFiles = '';

        foreach ($this->giftCounts() as $value => $giftCount) {
            $gift = Gift::from($value);
            $giftFiles .= '  '.self::giftCardsFile($gift)."\n"
                ."     {$giftCount} ".Str::plural('page', $giftCount).": one card per {$gift->getLabel()} package.\n\n";
        }

        $cards = $withCards ? <<<TXT
        READY-TO-PRINT CARDS (use these)

        {$giftFiles}  Every gift card has its OWN QR code, and shows its code as text at the
          bottom right (card_text in codes.csv). Within each file, cards are in serial
          order. All {$count} gift cards together match the {$count} rows of codes.csv.
          Print every page exactly ONCE. Never print a gift card twice.

          {$tryAgainCard}
             1 page. Print this same card for EVERY package without a gift.{$tryAgainText}

          QR codes and code text are vector art in chocolate brown {$color} (RGB).
          Please keep this colour exactly; do not lighten it.


        TXT : '';

        return <<<TXT
        Christmas gift QR codes for printing

        {$cards}BARE QR IMAGES (only if you build the cards yourself)

          svg/        One unique QR image per gift package, named by serial (e.g. MM-0001.svg).
          codes.csv   The same list as a table: serial, code, url, svg_file, card_text.
          {$tryAgain}  The one shared QR image for every package without a gift.

          Gift codes link to {$baseUrl}/r/<code>.
          The shared code links to {$baseUrl}/try-again.
          If you generate QR images yourself from the URLs, use error correction
          level Q and keep a white border of 4 modules around the code.

        Put the QR card inside the sealed package, so nobody can see it before opening.
        TXT;
    }
}
