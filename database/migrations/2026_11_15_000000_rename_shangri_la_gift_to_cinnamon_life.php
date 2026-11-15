<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The dinner voucher partner changed from Shangri-La to Cinnamon Life.
 * Gift names are never printed in the QR codes, so codes that were already
 * generated (or printed) keep working; only the stored gift value changes.
 */
return new class extends Migration
{
    private const OLD = 'shangri_la_dinner';

    private const NEW = 'cinnamon_life_dinner';

    public function up(): void
    {
        $this->rename(self::OLD, self::NEW);
    }

    public function down(): void
    {
        $this->rename(self::NEW, self::OLD);
    }

    private function rename(string $from, string $to): void
    {
        DB::transaction(function () use ($from, $to): void {
            foreach (['qr_codes', 'claims'] as $table) {
                DB::table($table)->where('gift', $from)->update(['gift' => $to]);
            }
        });
    }
};
