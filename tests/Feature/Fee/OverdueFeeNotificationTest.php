<?php

namespace Tests\Feature\Fee;

use App\Mail\OverdueFeeNotification;
use App\Models\Fee;
use App\Models\Member;
use App\Models\Sport;
use App\Models\FeeCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class OverdueFeeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailable_has_correct_subject(): void
    {
        $fees = $this->makeOverdueFees(3);
        $mail = new OverdueFeeNotification($fees, 35);

        $mail->assertHasSubject('Alerta: 3 socios con cuota vencida > 35 días');
    }

    public function test_mailable_contains_member_name(): void
    {
        $fees = $this->makeOverdueFees(1);
        $mail = new OverdueFeeNotification($fees, 35);

        $mail->assertSeeInHtml($fees->first()->member->name);
    }

    public function test_mailable_contains_period(): void
    {
        $fees = $this->makeOverdueFees(1);
        $mail = new OverdueFeeNotification($fees, 35);

        $mail->assertSeeInHtml($fees->first()->period);
    }

    private function makeOverdueFees(int $count): Collection
    {
        $sport = Sport::factory()->create();
        $category = FeeCategory::factory()->create();
        $period = now()->subMonths(2)->format('Y-m');

        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $member = Member::factory()->create([
                'sport_id' => $sport->id,
                'fee_category_id' => $category->id,
            ]);
            $ids[] = Fee::factory()->create([
                'member_id' => $member->id,
                'status' => 'pending',
                'period' => $period,
            ])->id;
        }

        return Fee::with('member')->whereIn('id', $ids)->get();
    }
}
