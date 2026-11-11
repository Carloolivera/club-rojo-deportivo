<?php

namespace Tests\Feature\Auth;

use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MemberPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_manager_cannot_view_member_from_different_sport(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $manager = User::factory()->create(['sport_id' => $football->id]);
        $manager->assignRole('manager');

        $hockeyMember = Member::factory()->create(['sport_id' => $hockey->id]);

        $this->assertFalse(Gate::forUser($manager)->allows('view', $hockeyMember));
    }

    public function test_manager_can_view_member_from_same_sport(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);

        $manager = User::factory()->create(['sport_id' => $football->id]);
        $manager->assignRole('manager');

        $member = Member::factory()->create(['sport_id' => $football->id]);

        $this->assertTrue(Gate::forUser($manager)->allows('view', $member));
    }

    public function test_admin_can_view_any_member(): void
    {
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $admin = User::factory()->create(['sport_id' => null]);
        $admin->assignRole('admin');

        $member = Member::factory()->create(['sport_id' => $hockey->id]);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $member));
    }

    public function test_manager_cannot_update_member_from_different_sport(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $manager = User::factory()->create(['sport_id' => $football->id]);
        $manager->assignRole('manager');

        $hockeyMember = Member::factory()->create(['sport_id' => $hockey->id]);

        $this->assertFalse(Gate::forUser($manager)->allows('update', $hockeyMember));
    }

    public function test_manager_can_update_member_from_same_sport(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);

        $manager = User::factory()->create(['sport_id' => $football->id]);
        $manager->assignRole('manager');

        $member = Member::factory()->create(['sport_id' => $football->id]);

        $this->assertTrue(Gate::forUser($manager)->allows('update', $member));
    }

    public function test_admin_can_delete_any_member(): void
    {
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $admin = User::factory()->create(['sport_id' => null]);
        $admin->assignRole('admin');

        $member = Member::factory()->create(['sport_id' => $hockey->id]);

        $this->assertTrue(Gate::forUser($admin)->allows('delete', $member));
    }

    public function test_manager_cannot_delete_member_from_different_sport(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $manager = User::factory()->create(['sport_id' => $football->id]);
        $manager->assignRole('manager');

        $hockeyMember = Member::factory()->create(['sport_id' => $hockey->id]);

        $this->assertFalse(Gate::forUser($manager)->allows('delete', $hockeyMember));
    }

    public function test_authority_cannot_view_members(): void
    {
        $football = Sport::factory()->create();

        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $member = Member::factory()->create(['sport_id' => $football->id]);

        $this->assertFalse(Gate::forUser($authority)->allows('view', $member));
    }
}
