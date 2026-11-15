<?php

use App\Enums\Gift;
use App\Models\QrCode;
use App\Services\QrCodeExporter;
use App\Services\QrCodeGenerator;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;

function generateCodes(int $spa = 200, int $cinnamonLife = 200): int
{
    return app(QrCodeGenerator::class)->generate([
        Gift::SpaCeylon->value => $spa,
        Gift::CinnamonLifeDinner->value => $cinnamonLife,
    ]);
}

it('generates one unique code per gift package and no codes for packages without a gift', function () {
    expect(generateCodes())->toBe(400);

    expect(QrCode::count())->toBe(400)
        ->and(QrCode::where('gift', Gift::SpaCeylon)->count())->toBe(200)
        ->and(QrCode::where('gift', Gift::CinnamonLifeDinner)->count())->toBe(200)
        ->and(QrCode::distinct()->count('code'))->toBe(400)
        ->and(QrCode::distinct()->count('serial'))->toBe(400);
});

it('makes long random codes and sequential serials', function () {
    generateCodes(spa: 10, cinnamonLife: 10);

    QrCode::all()->each(function (QrCode $qrCode) {
        expect($qrCode->code)->toMatch('/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{12}$/');
    });

    expect(QrCode::orderBy('id')->pluck('serial')->first())->toBe('MM-0001')
        ->and(QrCode::orderBy('id')->pluck('serial')->last())->toBe('MM-0020');
});

it('mixes the two gifts randomly across serial numbers', function () {
    generateCodes();

    // A fair shuffle almost never puts all 200 spa vouchers on the first 200 serials.
    $spaInFirstHalf = QrCode::orderBy('id')->limit(200)->pluck('gift')
        ->filter(fn (Gift $gift) => $gift === Gift::SpaCeylon)
        ->count();

    expect($spaInFirstHalf)->toBeLessThan(200)->toBeGreaterThan(0);
});

it('refuses to generate a second time, because codes are already printed', function () {
    generateCodes(spa: 1, cinnamonLife: 1);

    expect(fn () => generateCodes(spa: 1, cinnamonLife: 1))
        ->toThrow(RuntimeException::class, 'already been generated');

    expect(QrCode::count())->toBe(2);
});

it('rejects zero or negative gift numbers', function (int $spa, int $cinnamonLife) {
    expect(fn () => generateCodes(spa: $spa, cinnamonLife: $cinnamonLife))
        ->toThrow(InvalidArgumentException::class);

    expect(QrCode::count())->toBe(0);
})->with([
    'no gifts at all' => [0, 0],
    'negative' => [-1, 5],
]);

it('can be run from the command line', function () {
    $this->artisan('app:generate-qr-codes', ['--spa-ceylon' => 3, '--cinnamon-life' => 4])
        ->expectsConfirmation('Generate these codes? This can only be done once.', 'yes')
        ->assertSuccessful();

    expect(QrCode::count())->toBe(7)
        ->and(QrCode::where('gift', Gift::SpaCeylon)->count())->toBe(3)
        ->and(QrCode::where('gift', Gift::CinnamonLifeDinner)->count())->toBe(4);
});

it('exports the gift codes plus one shared try-again QR, without saying which gift each code wins', function () {
    config(['promo.qr_base_url' => 'https://promo.example.com']);
    config(['promo.card.template' => '/nowhere/card.pdf']); // bare QR export; cards are tested below
    generateCodes(spa: 2, cinnamonLife: 3);

    $path = app(QrCodeExporter::class)->toZip();

    $zip = new ZipArchive;
    $zip->open($path);
    $csv = $zip->getFromName('codes.csv');
    $svgCount = collect(range(0, $zip->numFiles - 1))
        ->filter(fn (int $i) => str_starts_with($zip->getNameIndex($i), 'svg/'))
        ->count();
    $firstSvg = $zip->getFromName('svg/MM-0001.svg');
    $tryAgainSvg = $zip->getFromName('try-again.svg');
    $readme = $zip->getFromName('README.txt');
    $zip->close();
    unlink($path);

    $rows = array_map(str_getcsv(...), array_filter(explode("\n", $csv)));
    $code = QrCode::where('serial', 'MM-0001')->value('code');

    expect($rows[0])->toBe(['serial', 'code', 'url', 'svg_file', 'card_text'])
        ->and($rows)->toHaveCount(6)
        ->and($rows[1])->toBe(['MM-0001', $code, "https://promo.example.com/r/{$code}", 'svg/MM-0001.svg', "MM-{$code}"])
        ->and($svgCount)->toBe(5)
        ->and($firstSvg)->toContain('<svg')->toContain('#461100') // same chocolate brown as the cards
        ->and($tryAgainSvg)->toContain('<svg')
        ->and($readme)->toContain('https://promo.example.com/try-again')
        ->and($readme)->not->toContain('READY-TO-PRINT CARDS')
        ->and(strtolower($csv))->not->toContain('spa_ceylon')->not->toContain('spa ceylon')
        ->not->toContain('cinnamon');
});

/** Every file in a ZIP, by name. */
function zipFiles(string $path): array
{
    $zip = new ZipArchive;
    $zip->open($path);
    $files = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $files[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
    }

    $zip->close();
    unlink($path);

    return $files;
}

/** The code text printed on each page of a card PDF, in page order. */
function cardTexts(string $pdf): array
{
    $reader = new PdfReader(new PdfParser(StreamReader::createByString($pdf)));

    return array_map(function (int $page) use ($reader): ?string {
        preg_match('/\((MM-[A-Z0-9]+)\) Tj/', $reader->getPage($page)->getContentStream(), $m);

        return $m[1] ?? null;
    }, range(1, $reader->getPageCount()));
}

it('puts each gift\'s cards in its own PDF, in serial order', function () {
    generateCodes(spa: 2, cinnamonLife: 3);

    $files = zipFiles(app(QrCodeExporter::class)->toZip());

    $spaFile = QrCodeExporter::giftCardsFile(Gift::SpaCeylon);
    $cinnamonFile = QrCodeExporter::giftCardsFile(Gift::CinnamonLifeDinner);
    $expectedTexts = fn (Gift $gift) => QrCode::where('gift', $gift)->orderBy('id')->pluck('code')->map(fn ($code) => "MM-{$code}")->all();

    expect($spaFile)->toBe('cards/spa-ceylon-gift-voucher-cards.pdf')
        ->and($cinnamonFile)->toBe('cards/cinnamon-life-dinner-voucher-cards.pdf')
        ->and(array_keys($files))->toContain($spaFile, $cinnamonFile, QrCodeExporter::TRY_AGAIN_CARD_FILE)
        ->and(cardTexts($files[$spaFile]))->toBe($expectedTexts(Gift::SpaCeylon))
        ->and(cardTexts($files[$cinnamonFile]))->toBe($expectedTexts(Gift::CinnamonLifeDinner))
        ->and(cardTexts($files[QrCodeExporter::TRY_AGAIN_CARD_FILE]))->toBe([null]) // one card, no code text
        ->and($files['README.txt'])->toContain('READY-TO-PRINT CARDS')
        ->and($files['README.txt'])->toContain("{$spaFile}\n     2 pages: one card per Spa Ceylon Gift Voucher package.")
        ->and($files['README.txt'])->toContain("{$cinnamonFile}\n     3 pages: one card per Cinnamon Life Dinner Voucher package.");

    // No temporary card PDFs left behind.
    expect(glob(storage_path('app/private/cards-*.pdf')))->toBe([]);
})->skip(! is_file(__DIR__.'/../../../public/git_qr/56x85 mm - QR Card - Front - Generate AW.pdf'), 'Card artwork PDF is not in this checkout.');

it('leaves out the PDF for a gift without any codes', function () {
    generateCodes(spa: 3, cinnamonLife: 0);

    $files = zipFiles(app(QrCodeExporter::class)->toZip());

    expect(array_keys($files))->toContain(QrCodeExporter::giftCardsFile(Gift::SpaCeylon))
        ->not->toContain(QrCodeExporter::giftCardsFile(Gift::CinnamonLifeDinner))
        ->and($files['README.txt'])->not->toContain('Cinnamon');
})->skip(! is_file(__DIR__.'/../../../public/git_qr/56x85 mm - QR Card - Front - Generate AW.pdf'), 'Card artwork PDF is not in this checkout.');

it('cleans up if building the cards fails halfway', function () {
    generateCodes(spa: 2, cinnamonLife: 2);
    config(['promo.card.code_text.font' => '/nowhere/font.json']); // fails on the first gift card

    expect(fn () => app(QrCodeExporter::class)->toZip())->toThrow(RuntimeException::class, 'Card font not found');

    // Neither the finished card PDFs nor the half-written ZIP are left behind.
    expect(glob(storage_path('app/private/cards-*.pdf')))->toBe([])
        ->and(glob(storage_path('app/private/qr-export-*.zip')))->toBe([]);
})->skip(! is_file(__DIR__.'/../../../public/git_qr/56x85 mm - QR Card - Front - Generate AW.pdf'), 'Card artwork PDF is not in this checkout.');

it('points the shared try-again QR at the try-again page', function () {
    config(['promo.qr_base_url' => 'https://promo.example.com/']);

    expect(QrCode::tryAgainUrl())->toBe('https://promo.example.com/try-again');
});
