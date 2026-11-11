<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GuardianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake('es_AR')->firstName() . ' ' . fake('es_AR')->lastName(),
            'dni' => fake()->optional(0.8)->numerify('########'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional(0.6)->safeEmail(),
            'relationship' => fake()->randomElement(['madre', 'padre', 'tutor', 'abuela', 'abuelo', 'tío', 'tía']),
        ];
    }
}
