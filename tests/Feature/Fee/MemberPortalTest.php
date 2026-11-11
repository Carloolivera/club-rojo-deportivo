<?php

namespace Tests\Feature\Fee;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    private function createMemberWithFees(): Member
    {
        $category = FeeCategory::factory()->create(['amount' => 3000]);
        $sport    = Sport::factory()->create();
        $member   = Member::factory()->create([
            'status' => 'active',
            'fee_category_id' => $category->id,
            'sport_id' => $sport->id,
        ]);
        Fee::factory()->create(['member_id' => $member->id, 'period' => '2026-02', 'status' => 'pending']);
        Fee::factory()->create(['member_id' => $member->id, 'period' => '2026-03', 'status' => 'pending']);
        Fee::factory()->create(['member_id' => $member->id, 'period' => '2026-04', 'status' => 'paid']);
        return $member;
    }

    public function test_member_can_access_fees_via_signed_url(): void
    {
        $member = $this->createMemberWithFees();
        $url = URL::signedRoute('member.fees', ['member' => $member->id]);

        $this->get($url)->assertOk()->assertViewIs('member.fees');
    }

    public function test_member_sees_their_fees(): void
    {
        $member = $this->createMemberWithFees();
        $url = URL::signedRoute('member.fees', ['member' => $member->id]);

        $response = $this->get($url);
        $response->assertViewHas('fees');
        $fees = $response->viewData('fees');
        $this->assertCount(3, $fees);
    }

    public function test_invalid_signature_returns_403(): void
    {
        $member = $this->createMemberWithFees();

        $this->get(route('member.fees', ['member' => $member->id]))
            ->assertStatus(403);
    }

    public function test_nonexistent_member_returns_404(): void
    {
        $url = URL::signedRoute('member.fees', ['member' => 99999]);
        $this->get($url)->assertNotFound();
    }

    public function test_portal_check_includes_fees_url_when_overdue(): void
    {
        $dni = '30111222';
        $member = Member::factory()->create(['dni' => $dni]);
        Fee::factory()->create(['member_id' => $member->id, 'status' => 'overdue']);

        $this->postJson(route('portal.check'), ['dni' => $dni])
            ->assertOk()
            ->assertJsonFragment(['status' => 'en_deuda'])
            ->assertJsonStructure(['status', 'fees_url']);
    }

    public function test_portal_check_includes_fees_url_when_al_dia(): void
    {
        $dni = '30111333';
        $member = Member::factory()->create(['dni' => $dni]);
        Fee::factory()->create(['member_id' => $member->id, 'status' => 'paid']);

        $this->postJson(route('portal.check'), ['dni' => $dni])
            ->assertOk()
            ->assertJsonFragment(['status' => 'al_dia'])
            ->assertJsonStructure(['status', 'fees_url']);
    }
}
