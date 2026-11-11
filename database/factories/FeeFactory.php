<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeFactory extends Factory
{
    public function definition(): array
    {
        $isPaid = fake()->boolean(70);

        return [
            'member_id' => Member::factory(),
            'period' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m'),
            'amount' => fake()->randomElement([3000, 5000, 8000, 10000]),
            'status' => $isPaid ? 'paid' : fake()->randomElement(['pending', 'overdue']),
            'paid_at' => $isPaid ? fake()->dateTimeBetween('-12 months', 'now') : null,
            'payment_method' => $isPaid ? fake()->randomElement(['mp', 'manual', 'transfer', 'cash']) : null,
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => fake()->randomElement(['mp', 'manual', 'transfer', 'cash']),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'paid_at' => null,
            'payment_method' => null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => 'overdue',
            'paid_at' => null,
            'payment_method' => null,
        ]);
    }
}
