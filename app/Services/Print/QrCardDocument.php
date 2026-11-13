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
     * The whole code is one filled path in 100% black (CMYK 0/0/0/100):
     * printers want small black detail on the black plate only, because
     * "rich" black from RGB can print with fuzzy edges if plates misalign.
     * Each horizontal run of dark modules is one rectangle, so there are
     * no hairline gaps inside the code.
     */
    public function qrCode(ByteMatrix $matrix, float $x, float $y, float $moduleSize): void
    {
        $operators = ['q', '0 0 0 1 k'];
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
}
