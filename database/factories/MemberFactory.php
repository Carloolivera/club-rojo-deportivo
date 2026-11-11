<?php

namespace Database\Factories;

use App\Models\FeeCategory;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    public function definition(): array
    {
        $birthdate = fake()->dateTimeBetween('-70 years', '-5 years');
        $age = now()->diffInYears($birthdate);

        return [
            'name' => fake('es_AR')->firstName() . ' ' . fake('es_AR')->lastName(),
            'dni' => (string) fake()->unique()->numberBetween(10_000_000, 99_999_999),
            'email' => fake()->optional(0.7)->safeEmail(),
            // Fixed local format: faker's phoneNumber() sometimes adds extensions ("x123") that the panel's tel() validation rejects, which made tests fail at random.
            'phone' => fake()->optional(0.8)->numerify('11 ####-####'),
            'address' => fake('es_AR')->optional(0.6)->address(),
            'birthdate' => $birthdate,
            'is_minor' => $age < 18,
            'sport_id' => Sport::factory(),
            'fee_category_id' => FeeCategory::factory(),
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'suspended']),
            'consent_at' => now()->subDays(fake()->numberBetween(1, 365)),
            'consent_ip' => fake()->ipv4(),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function minor(): static
    {
        return $this->state(function () {
            $birthdate = fake()->dateTimeBetween('-17 years', '-5 years');

            return [
                'birthdate' => $birthdate,
                'is_minor' => true,
            ];
        });
    }
}
