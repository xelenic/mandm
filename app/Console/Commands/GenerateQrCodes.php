<?php

namespace App\Console\Commands;

use App\Enums\Gift;
use App\Models\QrCode;
use App\Services\QrCodeGenerator;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

use function Laravel\Prompts\confirm;

class GenerateQrCodes extends Command
{
    protected $signature = 'app:generate-qr-codes
        {--spa-ceylon= : How many gift packages get a Spa Ceylon gift voucher}
        {--shangri-la= : How many gift packages get a Shangri-La dinner voucher}';

    protected $description = 'Generate one unique QR code per gift package for the Christmas promotion';

    public function handle(QrCodeGenerator $generator): int
    {
        $gifts = [
            Gift::SpaCeylon->value => (int) ($this->option('spa-ceylon') ?? config('promo.gifts.spa_ceylon')),
            Gift::ShangriLaDinner->value => (int) ($this->option('shangri-la') ?? config('promo.gifts.shangri_la_dinner')),
        ];

        $this->components->twoColumnDetail(Gift::SpaCeylon->getLabel(), (string) $gifts[Gift::SpaCeylon->value]);
        $this->components->twoColumnDetail(Gift::ShangriLaDinner->getLabel(), (string) $gifts[Gift::ShangriLaDinner->value]);
        $this->components->twoColumnDetail('Unique gift QR codes', (string) array_sum($gifts));
        $this->components->twoColumnDetail('Gift QR codes link to', rtrim((string) config('promo.qr_base_url'), '/').'/r/…');
        $this->components->twoColumnDetail('Shared "try again" QR links to', QrCode::tryAgainUrl());

        if (! confirm('Generate these codes? This can only be done once.', default: false)) {
            return self::FAILURE;
        }

        try {
            $count = $generator->generate($gifts);
        } catch (RuntimeException|InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("{$count} gift QR codes generated. Download the print file from the admin panel: QR codes → Export for printing.");

        return self::SUCCESS;
    }
}
