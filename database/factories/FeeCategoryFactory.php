<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FeeCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Junior', 'Adulto', 'Familiar', 'Senior', 'Infantil']),
            'amount' => fake()->randomElement([3000, 5000, 8000, 10000, 12000]),
            'is_active' => true,
        ];
    }
}
