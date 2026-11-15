<?php

namespace App\Services\Print;

use BaconQrCode\Encoder\ByteMatrix;
use setasign\Fpdi\Fpdi;

/**
 * FPDF/FPDI document that can draw a QR code as print-ready vector art.
 */
final class QrCardDocument extends Fpdi
{
    /**
     * Draw a QR code with its top-left module at ($x, $y), in the document's units.
     *
     * The whole code is one filled path in the given CMYK colour, set
     * directly in CMYK so the printer gets exactly these ink values. Each
     * horizontal run of dark modules is one rectangle, so there are no
     * hairline gaps inside the code.
     *
     * @param  array{0: int, 1: int, 2: int, 3: int}  $cmyk  Ink percentages: cyan, magenta, yellow, black.
     */
    public function qrCode(ByteMatrix $matrix, float $x, float $y, float $moduleSize, array $cmyk = [0, 0, 0, 100]): void
    {
        $operators = ['q', self::cmykFill($cmyk)];
        $size = $matrix->getWidth();

        for ($row = 0; $row < $size; $row++) {
            $column = 0;

            while ($column < $size) {
                if ($matrix->get($column, $row) !== 1) {
                    $column++;

                    continue;
                }

                $start = $column;

                while ($column < $size && $matrix->get($column, $row) === 1) {
                    $column++;
                }

                // PDF coordinates are in points, measured from the bottom-left corner.
                $operators[] = sprintf(
                    '%.4F %.4F %.4F %.4F re',
                    ($x + $start * $moduleSize) * $this->k,
                    ($this->h - $y - ($row + 1) * $moduleSize) * $this->k,
                    ($column - $start) * $moduleSize * $this->k,
                    $moduleSize * $this->k,
                );
            }
        }

        $operators[] = 'f';
        $operators[] = 'Q';

        $this->_out(implode("\n", $operators));
    }

    /**
     * Write one line of text in the current font, ending at $right (distance
     * from the page's left edge) on the given baseline, in the document's units.
     *
     * @param  array{0: int, 1: int, 2: int, 3: int}  $cmyk  Ink percentages: cyan, magenta, yellow, black.
     */
    public function rightAlignedText(string $text, float $right, float $baseline, array $cmyk): void
    {
        $this->_out(sprintf(
            'q %s BT %.4F %.4F Td (%s) Tj ET Q',
            self::cmykFill($cmyk),
            ($right - $this->GetStringWidth($text)) * $this->k,
            ($this->h - $baseline) * $this->k,
            $this->_escape($text),
        ));
    }

    /**
     * @param  array{0: int, 1: int, 2: int, 3: int}  $cmyk
     */
    private static function cmykFill(array $cmyk): string
    {
        return vsprintf('%.2F %.2F %.2F %.2F k', array_map(fn (int $ink): float => $ink / 100, $cmyk));
    }
}
