<?php

namespace Database\Seeders;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\Member;
use App\Models\MemberPeriod;
use App\Models\Sport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $sports = Sport::all();
        $categories = FeeCategory::all();

        for ($n = 0; $n < 500; $n++) {
            $member = Member::factory()->create([
                'sport_id' => $sports->random()->id,
                'fee_category_id' => $categories->random()->id,
            ]);

            if ($member->is_minor) {
                $guardian = Guardian::factory()->create();
                $member->guardians()->attach($guardian->id, ['is_primary' => true]);
            }

            MemberPeriod::create([
                'member_id' => $member->id,
                'sport_id' => $member->sport_id,
                'started_at' => now()->subMonths(rand(1, 24)),
                'ended_at' => $member->status === 'active' ? null : now()->subDays(rand(1, 180)),
            ]);

            $this->generateFees($member);
        }
    }

    private function generateFees(Member $member): void
    {
        for ($i = 11; $i >= 0; $i--) {
            $period = Carbon::now()->subMonths($i)->format('Y-m');
            $isPaid = rand(1, 100) <= 70;

            Fee::create([
                'member_id' => $member->id,
                'period' => $period,
                'amount' => $member->feeCategory->amount,
                'status' => $isPaid ? 'paid' : ($i > 0 ? 'overdue' : 'pending'),
                'paid_at' => $isPaid ? Carbon::now()->subMonths($i)->startOfMonth()->addDays(rand(0, $i === 0 ? max(0, now()->day - 1) : 14)) : null,
                'payment_method' => $isPaid ? fake()->randomElement(['mp', 'manual', 'transfer', 'cash']) : null,
            ]);
        }
    }
}
