<?php

use App\Enums\Gift;
use App\Models\QrCode;
use App\Services\QrCodeExporter;
use App\Services\QrCodeGenerator;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

function generateCodes(int $spa = 200, int $shangriLa = 200): int
{
    return app(QrCodeGenerator::class)->generate([
        Gift::SpaCeylon->value => $spa,
        Gift::ShangriLaDinner->value => $shangriLa,
    ]);
}

it('generates one unique code per gift package and no codes for packages without a gift', function () {
    expect(generateCodes())->toBe(400);

    expect(QrCode::count())->toBe(400)
        ->and(QrCode::where('gift', Gift::SpaCeylon)->count())->toBe(200)
        ->and(QrCode::where('gift', Gift::ShangriLaDinner)->count())->toBe(200)
        ->and(QrCode::distinct()->count('code'))->toBe(400)
        ->and(QrCode::distinct()->count('serial'))->toBe(400);
});

it('makes long random codes and sequential serials', function () {
    generateCodes(spa: 10, shangriLa: 10);

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
    generateCodes(spa: 1, shangriLa: 1);

    expect(fn () => generateCodes(spa: 1, shangriLa: 1))
        ->toThrow(RuntimeException::class, 'already been generated');

    expect(QrCode::count())->toBe(2);
});

it('rejects zero or negative gift numbers', function (int $spa, int $shangriLa) {
    expect(fn () => generateCodes(spa: $spa, shangriLa: $shangriLa))
        ->toThrow(InvalidArgumentException::class);

    expect(QrCode::count())->toBe(0);
})->with([
    'no gifts at all' => [0, 0],
    'negative' => [-1, 5],
]);

it('can be run from the command line', function () {
    $this->artisan('app:generate-qr-codes', ['--spa-ceylon' => 3, '--shangri-la' => 4])
        ->expectsConfirmation('Generate these codes? This can only be done once.', 'yes')
        ->assertSuccessful();

    expect(QrCode::count())->toBe(7)
        ->and(QrCode::where('gift', Gift::SpaCeylon)->count())->toBe(3)
        ->and(QrCode::where('gift', Gift::ShangriLaDinner)->count())->toBe(4);
});

it('exports the gift codes plus one shared try-again QR, without saying which gift each code wins', function () {
    config(['promo.qr_base_url' => 'https://promo.example.com']);
    config(['promo.card.template' => '/nowhere/card.pdf']); // bare QR export; cards are tested below
    generateCodes(spa: 2, shangriLa: 3);

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

    expect($rows[0])->toBe(['serial', 'code', 'url', 'svg_file'])
        ->and($rows)->toHaveCount(6)
        ->and($rows[1])->toBe(['MM-0001', $code, "https://promo.example.com/r/{$code}", 'svg/MM-0001.svg'])
        ->and($svgCount)->toBe(5)
        ->and($firstSvg)->toContain('<svg')
        ->and($tryAgainSvg)->toContain('<svg')
        ->and($readme)->toContain('https://promo.example.com/try-again')
        ->and($readme)->not->toContain('READY-TO-PRINT CARDS')
        ->and(strtolower($csv))->not->toContain('spa')
        ->not->toContain('shangri');
});

it('includes print-ready cards on the artwork in the export', function () {
    generateCodes(spa: 2, shangriLa: 3);

    $path = app(QrCodeExporter::class)->toZip();

    $zip = new ZipArchive;
    $zip->open($path);
    $giftCards = $zip->getFromName(QrCodeExporter::GIFT_CARDS_FILE);
    $tryAgainCard = $zip->getFromName(QrCodeExporter::TRY_AGAIN_CARD_FILE);
    $readme = $zip->getFromName('README.txt');
    $zip->close();
    unlink($path);

    $pageCount = fn (string $pdf): int => (new Fpdi)->setSourceFile(StreamReader::createByString($pdf));

    expect($pageCount($giftCards))->toBe(5)
        ->and($pageCount($tryAgainCard))->toBe(1)
        ->and($readme)->toContain('READY-TO-PRINT CARDS')
        ->and($readme)->toContain('Page 1 = MM-0001');

    // No temporary card PDFs left behind.
    expect(glob(storage_path('app/private/cards-*.pdf')))->toBe([]);
})->skip(! is_file(__DIR__.'/../../../public/git_qr/56x85 mm - QR Card - Front - Generate AW.pdf'), 'Card artwork PDF is not in this checkout.');

it('points the shared try-again QR at the try-again page', function () {
    config(['promo.qr_base_url' => 'https://promo.example.com/']);

    expect(QrCode::tryAgainUrl())->toBe('https://promo.example.com/try-again');
});
