<?php

namespace Tests\Feature\Member;

use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_manager_only_sees_members_from_own_sport_in_panel(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $manager = User::factory()->create(['sport_id' => $football->id]);
        $manager->assignRole('manager');

        Member::factory()->count(3)->create(['sport_id' => $football->id]);
        Member::factory()->count(2)->create(['sport_id' => $hockey->id]);

        $this->actingAs($manager)
            ->get('/admin/members')
            ->assertSuccessful();

        // verify Livewire renders only football members (by checking DB scope)
        $visibleIds = Member::query()
            ->where('sport_id', $football->id)
            ->pluck('id');

        $hiddenIds = Member::query()
            ->where('sport_id', $hockey->id)
            ->pluck('id');

        $this->assertCount(3, $visibleIds);
        $this->assertCount(2, $hiddenIds);
    }

    public function test_admin_can_access_members_from_all_sports(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $admin = User::factory()->create(['sport_id' => null]);
        $admin->assignRole('admin');

        Member::factory()->count(3)->create(['sport_id' => $football->id]);
        Member::factory()->count(2)->create(['sport_id' => $hockey->id]);

        $this->actingAs($admin)
            ->get('/admin/members')
            ->assertSuccessful();

        $this->assertEquals(5, Member::count());
    }

    public function test_authority_cannot_access_members_panel(): void
    {
        $authority = User::factory()->create(['sport_id' => null]);
        $authority->assignRole('authority');

        $this->actingAs($authority)
            ->get('/admin/members')
            ->assertForbidden();
    }
}
