<?php

namespace Database\Factories;

use App\Models\LeaveReason;
use App\Models\Member;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberPeriodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id'       => Member::factory(),
            'sport_id'        => Sport::factory(),
            'started_at'      => fake()->dateTimeBetween('-2 years', '-1 month'),
            'ended_at'        => null,
            'leave_reason_id' => null,
            'leave_note'      => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'ended_at'        => fake()->dateTimeBetween('-1 month', 'now'),
            'leave_reason_id' => LeaveReason::factory(),
            'leave_note'      => fake()->optional()->sentence(),
        ]);
    }
}
