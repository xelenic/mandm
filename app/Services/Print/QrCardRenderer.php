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

    public function __construct(private readonly ArtworkCleaner $artwork) {}

    public static function templateExists(): bool
    {
        return is_file((string) config('promo.card.template'));
    }

    /**
     * @param  iterable<string|array{url: string, text?: string|null}>  $cards  One card per item, in this order:
     *                                                                          the URL for its QR code, plus optional text printed at the bottom right.
     * @return string Path to a temporary PDF file. The caller deletes it.
     */
    public function render(iterable $cards, string $title): string
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
        $color = (string) config('promo.qr_color');
        $textStyle = config('promo.card.code_text');
        $fontLoaded = false;

        foreach ($cards as $card) {
            ['url' => $url, 'text' => $text] = is_string($card) ? ['url' => $card, 'text' => null] : $card + ['text' => null];

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height']);

            // Scale the code to fill the box, leaving $margin of white inside the outline.
            $matrix = Encoder::encode($url, ErrorCorrectionLevel::Q())->getMatrix();
            $module = ($box['size'] - 2 * $margin) / $matrix->getWidth();

            $pdf->qrCode($matrix, $box['x'] + $margin, $box['y'] + $margin, $module, $color);

            if ($text !== null && $text !== '') {
                if (! $fontLoaded) {
                    $this->loadFont($pdf, $textStyle['font']);
                    $fontLoaded = true;
                }

                $pdf->SetFont('CardCode', '', $textStyle['size']);
                $pdf->rightAlignedText($text, $size['width'] - $textStyle['right'], $textStyle['baseline'], $color);
            }
        }

        if ($pdf->PageNo() === 0) {
            throw new RuntimeException('No cards to print.');
        }

        $path = storage_path('app/private/cards-'.bin2hex(random_bytes(8)).'.pdf');
        $pdf->Output('F', $path);

        return $path;
    }

    /**
     * Register the FPDF font definition (.json, next to its .z font file) as "CardCode".
     */
    private function loadFont(QrCardDocument $pdf, string $definition): void
    {
        if (! is_file($definition)) {
            throw new RuntimeException("Card font not found at [{$definition}]. Check promo.card.code_text.font.");
        }

        $pdf->AddFont('CardCode', '', basename($definition), dirname($definition));
    }

    /**
     * The artwork PDF with its hidden layers kept hidden and, if configured,
     * the QR box outline removed (loaded once).
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

        $outline = config('promo.card.remove_box_outline') ? config('promo.card.qr_box') : null;

        return $this->template = $this->artwork->apply(file_get_contents($path), $outline);
    }
}
