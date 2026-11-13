<?php

namespace Database\Factories;

use App\Enums\Gift;
use App\Models\QrCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QrCode>
 */
class QrCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'serial' => 'MM-'.fake()->unique()->numerify('#####'),
            'code' => Str::upper(Str::random(12)),
            'gift' => fake()->randomElement(Gift::cases()),
        ];
    }

    public function forGift(Gift $gift): static
    {
        return $this->state(fn (array $attributes) => [
            'gift' => $gift,
        ]);
    }
}
