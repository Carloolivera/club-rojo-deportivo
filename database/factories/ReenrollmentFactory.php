<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReenrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id'    => Member::factory(),
            'requested_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'status'       => 'pending',
            'approved_at'  => null,
            'approved_by'  => null,
            'notes'        => fake()->optional()->sentence(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status'      => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status'      => 'rejected',
            'approved_at' => now(),
        ]);
    }
}
