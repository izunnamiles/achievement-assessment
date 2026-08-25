<?php

namespace Database\Factories;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reference' => (string) Str::uuid(),
            'amount' => fake()->numberBetween(100, 5000),
            'reason' => fake()->sentence(),
            'status' => PayoutStatus::Pending,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => PayoutStatus::Paid, 'verified_at' => now()]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => PayoutStatus::Failed]);
    }
}
