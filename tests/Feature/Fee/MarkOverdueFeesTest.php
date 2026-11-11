<?php

namespace Tests\Feature\Fee;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkOverdueFeesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_fee_from_past_month_is_marked_overdue(): void
    {
        $fee = $this->makeFee('pending', now()->subMonthNoOverflow()->format('Y-m'));

        $this->artisan('fees:mark-overdue')->assertSuccessful();

        $this->assertSame('overdue', $fee->fresh()->status);
    }

    public function test_pending_fee_from_current_month_stays_pending(): void
    {
        $fee = $this->makeFee('pending', now()->format('Y-m'));

        $this->artisan('fees:mark-overdue')->assertSuccessful();

        $this->assertSame('pending', $fee->fresh()->status);
    }

    public function test_paid_fee_from_past_month_is_unchanged(): void
    {
        $fee = $this->makeFee('paid', now()->subMonthNoOverflow()->format('Y-m'));

        $this->artisan('fees:mark-overdue')->assertSuccessful();

        $this->assertSame('paid', $fee->fresh()->status);
    }

    public function test_already_overdue_fee_is_unchanged(): void
    {
        $fee = $this->makeFee('overdue', now()->subMonthNoOverflow()->format('Y-m'));

        $this->artisan('fees:mark-overdue')->assertSuccessful();

        $this->assertSame('overdue', $fee->fresh()->status);
    }

    public function test_multiple_pending_past_fees_from_different_members_all_marked_overdue(): void
    {
        $period = now()->subMonths(2)->format('Y-m');

        // Cada fee necesita un miembro distinto (unique constraint: member_id + period)
        $fees = collect(range(1, 3))->map(
            fn () => $this->makeFee('pending', $period)
        );

        $this->artisan('fees:mark-overdue')->assertSuccessful();

        foreach ($fees as $fee) {
            $this->assertSame('overdue', $fee->fresh()->status);
        }
    }

    private function makeFee(string $status, string $period): Fee
    {
        return Fee::factory()->create([
            'member_id' => $this->makeMember()->id,
            'status'    => $status,
            'period'    => $period,
        ]);
    }

    private function makeMember(): Member
    {
        return Member::factory()->create([
            'sport_id'        => Sport::factory()->create()->id,
            'fee_category_id' => FeeCategory::factory()->create()->id,
            'status'          => 'active',
        ]);
    }
}
