<?php

namespace Tests\Feature\Fee;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateMonthlyFeesTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_fee_for_each_active_member(): void
    {
        $category = FeeCategory::factory()->create(['amount' => 3000]);
        $sport    = Sport::factory()->create();

        $active   = Member::factory()->count(3)->create([
            'status' => 'active',
            'fee_category_id' => $category->id,
            'sport_id' => $sport->id,
        ]);
        Member::factory()->create([
            'status' => 'inactive',
            'fee_category_id' => $category->id,
            'sport_id' => $sport->id,
        ]);

        $this->artisan('fees:generate', ['period' => '2026-04'])
            ->assertSuccessful();

        $this->assertCount(3, Fee::where('period', '2026-04')->get());
        foreach ($active as $member) {
            $this->assertDatabaseHas('fees', [
                'member_id' => $member->id,
                'period'    => '2026-04',
                'amount'    => 3000,
                'status'    => 'pending',
            ]);
        }
    }

    public function test_skips_existing_fee_for_period(): void
    {
        $category = FeeCategory::factory()->create(['amount' => 3000]);
        $sport    = Sport::factory()->create();
        $member   = Member::factory()->create([
            'status' => 'active',
            'fee_category_id' => $category->id,
            'sport_id' => $sport->id,
        ]);

        Fee::factory()->create([
            'member_id' => $member->id,
            'period'    => '2026-04',
            'status'    => 'paid',
        ]);

        $this->artisan('fees:generate', ['period' => '2026-04'])
            ->assertSuccessful();

        $this->assertCount(1, Fee::where('member_id', $member->id)->where('period', '2026-04')->get());
    }

    public function test_uses_current_month_when_no_period_given(): void
    {
        $category = FeeCategory::factory()->create(['amount' => 3000]);
        $sport    = Sport::factory()->create();
        Member::factory()->create([
            'status' => 'active',
            'fee_category_id' => $category->id,
            'sport_id' => $sport->id,
        ]);

        $this->artisan('fees:generate')->assertSuccessful();

        $expected = now()->format('Y-m');
        $this->assertDatabaseHas('fees', ['period' => $expected]);
    }
}
