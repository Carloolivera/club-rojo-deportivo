<?php

namespace Tests\Feature\Auth;

use App\Models\Fee;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FeePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_manager_can_view_fee_from_same_sport(): void
    {
        $sport = Sport::factory()->create();
        $manager = User::factory()->create(['sport_id' => $sport->id]);
        $manager->assignRole('manager');

        $member = Member::factory()->create(['sport_id' => $sport->id]);
        $fee = Fee::factory()->create(['member_id' => $member->id]);

        $this->assertTrue(Gate::forUser($manager)->allows('view', $fee));
    }

    public function test_manager_cannot_view_fee_from_different_sport(): void
    {
        $sport1 = Sport::factory()->create();
        $sport2 = Sport::factory()->create();
        $manager = User::factory()->create(['sport_id' => $sport1->id]);
        $manager->assignRole('manager');

        $member = Member::factory()->create(['sport_id' => $sport2->id]);
        $fee = Fee::factory()->create(['member_id' => $member->id]);

        $this->assertFalse(Gate::forUser($manager)->allows('view', $fee));
    }

    public function test_admin_can_view_any_fee(): void
    {
        $sport = Sport::factory()->create();
        $admin = User::factory()->create(['sport_id' => null]);
        $admin->assignRole('admin');

        $member = Member::factory()->create(['sport_id' => $sport->id]);
        $fee = Fee::factory()->create(['member_id' => $member->id]);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $fee));
    }

    public function test_manager_can_update_fee_from_same_sport(): void
    {
        $sport = Sport::factory()->create();
        $manager = User::factory()->create(['sport_id' => $sport->id]);
        $manager->assignRole('manager');

        $member = Member::factory()->create(['sport_id' => $sport->id]);
        $fee = Fee::factory()->create(['member_id' => $member->id]);

        $this->assertTrue(Gate::forUser($manager)->allows('update', $fee));
    }

    public function test_manager_cannot_update_fee_from_different_sport(): void
    {
        $sport1 = Sport::factory()->create();
        $sport2 = Sport::factory()->create();
        $manager = User::factory()->create(['sport_id' => $sport1->id]);
        $manager->assignRole('manager');

        $member = Member::factory()->create(['sport_id' => $sport2->id]);
        $fee = Fee::factory()->create(['member_id' => $member->id]);

        $this->assertFalse(Gate::forUser($manager)->allows('update', $fee));
    }

    public function test_authority_cannot_view_fees(): void
    {
        $sport = Sport::factory()->create();
        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $member = Member::factory()->create(['sport_id' => $sport->id]);
        $fee = Fee::factory()->create(['member_id' => $member->id]);

        $this->assertFalse(Gate::forUser($authority)->allows('view', $fee));
    }
}
