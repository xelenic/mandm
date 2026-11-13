<?php

namespace App\Services\Print;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PageBoundaries;

/**
 * Prints QR codes onto the designer's card artwork (config promo.card):
 * one card per page, with the QR code centred in the artwork's QR box.
 *
 * The artwork is embedded once and reused on every page, so a 400-card
 * PDF is about the size of the artwork itself.
 */
final class QrCardRenderer
{
    private ?string $template = null;

    public function __construct(private readonly HiddenLayerRemover $hiddenLayers) {}

    public static function templateExists(): bool
    {
        return is_file((string) config('promo.card.template'));
    }

    /**
     * @param  iterable<string>  $urls  One card is printed per URL, in this order.
     * @return string Path to a temporary PDF file. The caller deletes it.
     */
    public function render(iterable $urls, string $title): string
    {
        $pdf = new QrCardDocument('P', 'mm');
        $pdf->SetTitle($title);
        $pdf->SetCreator((string) config('promo.name'));
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->setSourceFile(StreamReader::createByString($this->template()));

        $template = $pdf->importPage(1, PageBoundaries::MEDIA_BOX);
        $size = $pdf->getTemplateSize($template);
        $box = config('promo.card.qr_box');
        $margin = (float) config('promo.card.qr_margin');

        foreach ($urls as $url) {
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height']);

            // Scale the code to fill the box, leaving $margin of white inside the outline.
            $matrix = Encoder::encode($url, ErrorCorrectionLevel::Q())->getMatrix();
            $module = ($box['size'] - 2 * $margin) / $matrix->getWidth();

            $pdf->qrCode($matrix, $box['x'] + $margin, $box['y'] + $margin, $module);
        }

        if ($pdf->PageNo() === 0) {
            throw new RuntimeException('No cards to print.');
        }

        $path = storage_path('app/private/cards-'.bin2hex(random_bytes(8)).'.pdf');
        $pdf->Output('F', $path);

        return $path;
    }

    /**
     * The artwork PDF with its hidden layers kept hidden (loaded once).
     */
    private function template(): string
    {
        if ($this->template !== null) {
            return $this->template;
        }

        $path = (string) config('promo.card.template');

        if (! is_file($path)) {
            throw new RuntimeException("Card artwork not found at [{$path}]. Check promo.card.template.");
        }

        return $this->template = $this->hiddenLayers->apply(file_get_contents($path));
    }
}
