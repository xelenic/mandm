<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\QrCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'qr_code_id' => QrCode::factory(),
            'gift' => fn (array $attributes) => QrCode::find($attributes['qr_code_id'])->gift,
            'reference' => 'XMAS-'.Str::upper(Str::random(8)),
            'name' => fake()->name(),
            'phone' => '+947'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'nic' => fake()->numerify('############'),
            'status' => ClaimStatus::Pending,
        ];
    }
}
