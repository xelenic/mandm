<?php

namespace App\Services;

use App\Enums\Gift;
use App\Models\QrCode;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Random\Engine\Secure;
use Random\Randomizer;
use RuntimeException;

/**
 * Creates the unique QR codes for the gift packages, all in one go.
 *
 * Packages without a gift don't get a code here; they all carry the same
 * shared "try again" QR code (QrCode::tryAgainUrl()).
 *
 * Fairness and security:
 * - Codes are 12 random characters (about 60 bits), so they can't be
 *   guessed to claim someone else's gift.
 * - Gifts are given to serial numbers with a cryptographically secure
 *   shuffle, so a serial says nothing about which gift it wins.
 */
class QrCodeGenerator
{
    /** No 0/O or 1/I, so a code is never misread if typed by hand. */
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const CODE_LENGTH = 12;

    public const SERIAL_PREFIX = 'MM-';

    /**
     * @param  array<string, int>  $gifts  Gift value => number of gift packages.
     * @return int Number of codes created.
     *
     * @throws RuntimeException When codes already exist (they may already be printed).
     * @throws InvalidArgumentException When the numbers are invalid.
     */
    public function generate(array $gifts): int
    {
        if (QrCode::query()->exists()) {
            throw new RuntimeException('QR codes have already been generated. Printed codes must never be regenerated.');
        }

        foreach ($gifts as $gift => $count) {
            if (Gift::tryFrom($gift) === null) {
                throw new InvalidArgumentException("Unknown gift [{$gift}].");
            }

            if ($count < 0) {
                throw new InvalidArgumentException('Gift quantities cannot be negative.');
            }
        }

        $total = array_sum($gifts);

        if ($total < 1) {
            throw new InvalidArgumentException('Enter at least one gift.');
        }

        $randomizer = new Randomizer(new Secure);

        $slots = [];

        foreach ($gifts as $gift => $count) {
            array_push($slots, ...array_fill(0, $count, $gift));
        }

        $slots = $randomizer->shuffleArray($slots);

        $codes = $this->uniqueCodes($randomizer, $total);
        $serialWidth = max(4, strlen((string) $total));
        $now = now();
        $rows = [];

        foreach ($slots as $index => $gift) {
            $rows[] = [
                'serial' => self::SERIAL_PREFIX.str_pad((string) ($index + 1), $serialWidth, '0', STR_PAD_LEFT),
                'code' => $codes[$index],
                'gift' => $gift,
                'scan_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // All or nothing. The unique serial index also stops two simultaneous
        // runs from both succeeding.
        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 500) as $chunk) {
                QrCode::query()->insert($chunk);
            }
        });

        return $total;
    }

    /**
     * @return list<string>
     */
    private function uniqueCodes(Randomizer $randomizer, int $count): array
    {
        $codes = [];

        while (count($codes) < $count) {
            $codes[$randomizer->getBytesFromString(self::ALPHABET, self::CODE_LENGTH)] = true;
        }

        // Cast back: PHP turns an all-digit array key into an int.
        return array_map(strval(...), array_keys($codes));
    }
}
