<?php

use App\Services\Print\ArtworkCleaner;
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

        $cleaned = app(ArtworkCleaner::class)->apply($original);
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

        expect(app(ArtworkCleaner::class)->apply($plain))->toBe($plain);
    });

    it('removes the QR box outline and nothing else', function () {
        $original = file_get_contents(CARD_ARTWORK);

        $cleaned = app(ArtworkCleaner::class)->apply($original, config('promo.card.qr_box'));
        $before = preg_replace('/\s+/', ' ', pageContent($original));
        $after = preg_replace('/\s+/', ' ', pageContent($cleaned));

        $strokes = fn (string $content): int => preg_match_all('/ S(?= |$)/', $content);

        expect($after)->toContain('121.131 75.582 -83.521 83.521 re n')
            ->and($after)->not->toContain('121.131 75.582 -83.521 83.521 re S')
            ->and($strokes($after))->toBe($strokes($before) - 1)  // only that one outline
            ->and($after)->toContain('/Im0 Do')
            ->and($after)->toContain('/Im1 Do');
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

    it('scales the QR code to fill the artwork\'s box, in chocolate brown, leaving the configured white margin', function (float $marginMm, float $marginPt) {
        config(['promo.card.qr_margin' => $marginMm]);
        $path = app(QrCardRenderer::class)->render(['https://promo.example.com/r/AAAAAAAAAAAA'], 'Card');
        $content = pageContent(file_get_contents($path));
        unlink($path);

        // The QR code is the block of rectangles drawn in #461100.
        expect($content)->toContain('0.275 0.067 0.000 rg');
        $qr = substr($content, strpos($content, '0.275 0.067 0.000 rg'));
        preg_match_all('/([\d.]+) ([\d.]+) ([\d.]+) ([\d.]+) re/', $qr, $m, PREG_SET_ORDER);

        $left = min(array_map(fn ($r) => (float) $r[1], $m));
        $right = max(array_map(fn ($r) => (float) $r[1] + (float) $r[3], $m));
        $bottom = min(array_map(fn ($r) => (float) $r[2], $m));
        $top = max(array_map(fn ($r) => (float) $r[2] + (float) $r[4], $m));

        // Box in PDF points: x 37.610–121.131, y 75.582–159.103.
        expect(count($m))->toBeGreaterThan(100)
            ->and(round($left - 37.610, 1))->toEqual($marginPt)
            ->and(round(121.131 - $right, 1))->toEqual($marginPt)
            ->and(round($bottom - 75.582, 1))->toEqual($marginPt)
            ->and(round(159.103 - $top, 1))->toEqual($marginPt);
    })->with([
        'no margin: fills the box exactly' => [0.0, 0.0],
        '0.75 mm margin' => [0.75, 2.1],
        '1 mm margin' => [1.0, 2.8],
    ]);

    it('by default fills the box, with the box outline removed', function () {
        expect((float) config('promo.card.qr_margin'))->toBe(0.0)
            ->and(config('promo.card.remove_box_outline'))->toBeTrue();
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

    it('prints the code text at the bottom right, mirroring the Terms text, in the card font', function () {
        $path = app(QrCardRenderer::class)->render([
            ['url' => 'https://promo.example.com/r/QNTU8RJF7YCH', 'text' => 'MM-QNTU8RJF7YCH'],
        ], 'Card');
        $pdf = file_get_contents($path);
        $content = preg_replace('/\s+/', ' ', pageContent($pdf));
        unlink($path);

        expect($pdf)->toMatch('#/BaseFont /([A-Z]{6}\+)?StintUltraCondensed-Regular#')  // the font is embedded
            ->and($content)->toMatch('/0\.275 0\.067 0\.000 rg BT [\d.]+ [\d.]+ Td \(MM-QNTU8RJF7YCH\) Tj ET/');

        preg_match('/BT ([\d.]+) ([\d.]+) Td \(MM-QNTU8RJF7YCH\)/', $content, $m);

        // Text width from the font's own metrics, at 10.5 pt.
        $font = json_decode(file_get_contents(config('promo.card.code_text.font')), true);
        $width = array_sum(array_map(fn (string $char) => $font['cw'][ord($char)], str_split('MM-QNTU8RJF7YCH'))) * 10.5 / 1000;
        $mm = 72 / 25.4;

        // Ends 3.08 mm from the right edge (56 mm card); baseline 82.81 mm from the top (85 mm card).
        expect(round((float) $m[1] + $width, 1))->toBe(round((56 - 3.08) * $mm, 1))
            ->and(round((float) $m[2], 1))->toBe(round((85 - 82.81) * $mm, 1));
    });

    it('prints no text on cards without any', function () {
        $path = app(QrCardRenderer::class)->render(['https://promo.example.com/try-again'], 'Card');
        $pdf = file_get_contents($path);
        unlink($path);

        expect(pageContent($pdf))->not->toContain('Tj')
            ->and($pdf)->not->toContain('StintUltraCondensed');
    });

    it('explains clearly when the card font is missing', function () {
        config(['promo.card.code_text.font' => '/nowhere/font.json']);

        expect(fn () => app(QrCardRenderer::class)->render([['url' => 'https://x.test/r/A', 'text' => 'MM-A']], 'Card'))
            ->toThrow(RuntimeException::class, 'Card font not found');
    });
})->skip(! is_file(CARD_ARTWORK), 'Card artwork PDF is not in this checkout.');
