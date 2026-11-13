<?php

use App\Services\Print\HiddenLayerRemover;
use App\Services\Print\QrCardRenderer;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;

const CARD_ARTWORK = __DIR__.'/../../../public/git_qr/56x85 mm - QR Card - Front - Generate AW.pdf';

/** Decoded drawing instructions of one page. */
function pageContent(string $pdf, int $page = 1): string
{
    return (new PdfReader(new PdfParser(StreamReader::createByString($pdf))))->getPage($page)->getContentStream();
}

describe('hiding the artwork\'s switched-off layer', function () {
    it('stops the hidden full-page layer from painting, but keeps the visible artwork', function () {
        $original = file_get_contents(CARD_ARTWORK);

        $cleaned = app(HiddenLayerRemover::class)->apply($original);
        $content = preg_replace('/\s+/', ' ', pageContent($cleaned));

        expect($cleaned)->toStartWith(rtrim($original, "\r\n")) // original bytes untouched
            ->and($content)->toContain('/Im0 Do')                // background artwork still drawn
            ->and($content)->toContain('121.131 75.582 -83.521 83.521 re S') // the QR box outline
            ->and($content)->toContain('0 8.504 158.74 223.937 re n')        // hidden layer: not painted
            ->and($content)->not->toContain('0 8.504 158.74 223.937 re f');
    });

    it('returns a PDF without layers unchanged', function () {
        $pdf = new Fpdi;
        $pdf->AddPage();
        $plain = $pdf->Output('S');

        expect(app(HiddenLayerRemover::class)->apply($plain))->toBe($plain);
    });
})->skip(! is_file(CARD_ARTWORK), 'Card artwork PDF is not in this checkout.');

describe('rendering QR cards', function () {
    it('prints one card per URL at the artwork\'s exact size', function () {
        $path = app(QrCardRenderer::class)->render([
            'https://promo.example.com/r/AAAAAAAAAAAA',
            'https://promo.example.com/r/BBBBBBBBBBBB',
            'https://promo.example.com/r/CCCCCCCCCCCC',
        ], 'Gift cards');

        $pdf = new Fpdi;
        $pages = $pdf->setSourceFile($path);
        $size = $pdf->getTemplateSize($pdf->importPage(1), null, null);
        unlink($path);

        expect($pages)->toBe(3)
            ->and(round($size['width'], 1))->toBe(56.0)   // mm
            ->and(round($size['height'], 1))->toBe(85.0);
    });

    it('scales the QR code to fill the artwork\'s box, in 100% black, leaving the configured white margin', function () {
        config(['promo.card.qr_margin' => 1.0]); // mm
        $path = app(QrCardRenderer::class)->render(['https://promo.example.com/r/AAAAAAAAAAAA'], 'Card');
        $content = pageContent(file_get_contents($path));
        unlink($path);

        // The QR code is the block of rectangles drawn in CMYK black.
        $qr = substr($content, strpos($content, '0 0 0 1 k'));
        preg_match_all('/([\d.]+) ([\d.]+) ([\d.]+) ([\d.]+) re/', $qr, $m, PREG_SET_ORDER);

        $left = min(array_map(fn ($r) => (float) $r[1], $m));
        $right = max(array_map(fn ($r) => (float) $r[1] + (float) $r[3], $m));
        $bottom = min(array_map(fn ($r) => (float) $r[2], $m));
        $top = max(array_map(fn ($r) => (float) $r[2] + (float) $r[4], $m));

        // Box in PDF points: x 37.610–121.131, y 75.582–159.103. 1 mm = 2.835 pt of white on each side.
        expect(count($m))->toBeGreaterThan(100)
            ->and(round($left - 37.610, 1))->toBe(2.8)
            ->and(round(121.131 - $right, 1))->toBe(2.8)
            ->and(round($bottom - 75.582, 1))->toBe(2.8)
            ->and(round(159.103 - $top, 1))->toBe(2.8);
    });

    it('embeds the artwork once, however many cards there are', function () {
        $renderer = app(QrCardRenderer::class);
        $one = $renderer->render(['https://promo.example.com/r/AAAAAAAAAAAA'], 'Card');
        $fifty = $renderer->render(array_map(fn ($i) => "https://promo.example.com/r/CODE{$i}", range(1, 50)), 'Cards');

        $growth = filesize($fifty) - filesize($one);
        unlink($one);
        unlink($fifty);

        expect($growth)->toBeLessThan(200 * 1024); // 49 extra cards add only their QR codes
    });

    it('explains clearly when the artwork file is missing', function () {
        config(['promo.card.template' => '/nowhere/card.pdf']);

        expect(fn () => app(QrCardRenderer::class)->render(['https://x.test/r/A'], 'Card'))
            ->toThrow(RuntimeException::class, 'Card artwork not found');
    });
})->skip(! is_file(CARD_ARTWORK), 'Card artwork PDF is not in this checkout.');
