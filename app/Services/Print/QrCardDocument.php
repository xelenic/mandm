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
     * The whole code is one filled path in the given colour. Each
     * horizontal run of dark modules is one rectangle, so there are no
     * hairline gaps inside the code.
     *
     * @param  string  $color  Hex colour, e.g. "#461100".
     */
    public function qrCode(ByteMatrix $matrix, float $x, float $y, float $moduleSize, string $color = '#000000'): void
    {
        $operators = ['q', self::rgbFill($color)];
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
     * @param  string  $color  Hex colour, e.g. "#461100".
     */
    public function rightAlignedText(string $text, float $right, float $baseline, string $color): void
    {
        $this->_out(sprintf(
            'q %s BT %.4F %.4F Td (%s) Tj ET Q',
            self::rgbFill($color),
            ($right - $this->GetStringWidth($text)) * $this->k,
            ($this->h - $baseline) * $this->k,
            $this->_escape($text),
        ));
    }

    /**
     * PDF fill-colour operator for a hex colour, in RGB like the artwork's own
     * text (e.g. #461100 → "0.275 0.067 0.000 rg").
     */
    private static function rgbFill(string $color): string
    {
        return vsprintf('%.3F %.3F %.3F rg', array_map(fn (int $channel): float => $channel / 255, HexColor::toRgb($color)));
    }
}
