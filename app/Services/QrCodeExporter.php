<?php

namespace App\Services;

use App\Models\QrCode;
use App\Services\Print\QrCardRenderer;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use RuntimeException;
use ZipArchive;

/**
 * Builds the file sent to the printer: a ZIP containing
 * - cards/gift-cards.pdf      print-ready cards on the designer's artwork, one per gift package,
 * - cards/try-again-card.pdf  the single card for every package without a gift,
 * - svg/MM-0001.svg …         the bare QR images, for printers who build cards themselves,
 * - codes.csv                 the gift codes as a table,
 * - try-again.svg             the bare shared "try again" QR image,
 * - README.txt                printing instructions.
 *
 * It says nothing about which gift each code wins.
 */
class QrCodeExporter
{
    public const TRY_AGAIN_FILE = 'try-again.svg';

    public const GIFT_CARDS_FILE = 'cards/gift-cards.pdf';

    public const TRY_AGAIN_CARD_FILE = 'cards/try-again-card.pdf';

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

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['serial', 'code', 'url', 'svg_file'], escape: '');

        $writer = $this->writer();

        QrCode::query()->lazyById(500)->each(function (QrCode $qrCode) use ($zip, $csv, $writer): void {
            $file = "svg/{$qrCode->serial}.svg";

            fputcsv($csv, [$qrCode->serial, $qrCode->code, $qrCode->url(), $file], escape: '');
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

        // ZipArchive reads added files on close(), so only delete them now.
        array_map(unlink(...), $cardFiles);

        return $path;
    }

    /**
     * @return array<string, string> Name in the ZIP => temporary PDF path.
     */
    private function renderCards(): array
    {
        $giftUrls = (function () {
            // Same order as codes.csv: page 1 is MM-0001, page 2 is MM-0002, ...
            foreach (QrCode::query()->lazyById(500) as $qrCode) {
                yield $qrCode->url();
            }
        })();

        return [
            self::GIFT_CARDS_FILE => $this->cards->render($giftUrls, 'Gift cards'),
            self::TRY_AGAIN_CARD_FILE => $this->cards->render([QrCode::tryAgainUrl()], 'Try again card'),
        ];
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
        // Margin 4 = the quiet zone the QR spec requires for reliable scanning.
        return new Writer(new ImageRenderer(new RendererStyle($size, 4), new SvgImageBackEnd));
    }

    private function readme(bool $withCards): string
    {
        $count = QrCode::query()->count();
        $baseUrl = rtrim((string) config('promo.qr_base_url'), '/');
        $tryAgain = self::TRY_AGAIN_FILE;
        $giftCards = self::GIFT_CARDS_FILE;
        $tryAgainCard = self::TRY_AGAIN_CARD_FILE;

        $cards = $withCards ? <<<TXT
        READY-TO-PRINT CARDS (use these)

          {$giftCards}
             {$count} pages, one card per gift package, each with its OWN QR code.
             Page 1 = MM-0001, page 2 = MM-0002, ... (same order as codes.csv).
             Print every page exactly ONCE. Never print a gift card twice.

          {$tryAgainCard}
             1 page. Print this same card for EVERY package without a gift.

          QR codes are vector art in 100% black (K only).


        TXT : '';

        return <<<TXT
        Christmas gift QR codes for printing

        {$cards}BARE QR IMAGES (only if you build the cards yourself)

          svg/        One unique QR image per gift package, named by serial (e.g. MM-0001.svg).
          codes.csv   The same list as a table: serial, code, url, svg_file.
          {$tryAgain}  The one shared QR image for every package without a gift.

          Gift codes link to {$baseUrl}/r/<code>.
          The shared code links to {$baseUrl}/try-again.
          If you generate QR images yourself from the URLs, use error correction
          level Q and keep a white border of 4 modules around the code.

        Put the QR card inside the sealed package, so nobody can see it before opening.
        TXT;
    }
}
