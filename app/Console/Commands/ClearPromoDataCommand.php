<?php

namespace App\Console\Commands;

use App\Actions\ClearPromoData;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class ClearPromoDataCommand extends Command
{
    protected $signature = 'app:clear-promo-data
        {--force : Skip the confirmation (for scripts)}';

    protected $description = 'Delete every QR code and gift claim, so codes can be generated again';

    public function handle(ClearPromoData $clear): int
    {
        $counts = $clear->counts();

        if ($counts['qr_codes'] === 0 && $counts['claims'] === 0) {
            $this->components->info('There is no promo data to clear.');

            return self::SUCCESS;
        }

        $this->components->warn('This permanently deletes all promo data. Printed QR codes will stop working.');
        $this->components->twoColumnDetail('QR codes', "{$counts['qr_codes']} ({$counts['scanned']} scanned)");
        $this->components->twoColumnDetail('Gift claims (winner details)', (string) $counts['claims']);

        if (! $this->option('force')) {
            $answer = text('Type DELETE to confirm', required: true);

            if ($answer !== 'DELETE') {
                $this->components->error('Nothing was deleted.');

                return self::FAILURE;
            }
        }

        $deleted = $clear->handle(via: 'console');

        $this->components->info("Deleted {$deleted['qr_codes']} QR codes and {$deleted['claims']} claims.");

        return self::SUCCESS;
    }
}
