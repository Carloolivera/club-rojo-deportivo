<?php

namespace Tests\Feature\Auth;

use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureSportScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        Route::middleware(['web', 'auth', 'sport.scope'])
            ->get('/test-members/{member}', fn (Member $member) => response()->json(['ok' => true]));
    }

    public function test_manager_gets_403_accessing_member_from_different_sport(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $manager = User::factory()->create(['sport_id' => $football->id]);
        $manager->assignRole('manager');

        $hockeyMember = Member::factory()->create(['sport_id' => $hockey->id]);

        $this->actingAs($manager)
            ->get("/test-members/{$hockeyMember->id}")
            ->assertForbidden();
    }

    public function test_idor_attempt_is_logged_in_activity_log(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $manager = User::factory()->create(['sport_id' => $football->id]);
        $manager->assignRole('manager');

        $hockeyMember = Member::factory()->create(['sport_id' => $hockey->id]);

        $this->actingAs($manager)
            ->get("/test-members/{$hockeyMember->id}");

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'security',
            'description' => 'idor_attempt',
        ]);
    }

    public function test_manager_can_access_member_from_same_sport(): void
    {
        $football = Sport::factory()->create(['name' => 'Fútbol']);

        $manager = User::factory()->create(['sport_id' => $football->id]);
        $manager->assignRole('manager');

        $footballMember = Member::factory()->create(['sport_id' => $football->id]);

        $this->actingAs($manager)
            ->get("/test-members/{$footballMember->id}")
            ->assertOk();
    }

    public function test_admin_can_access_member_from_any_sport(): void
    {
        $hockey = Sport::factory()->create(['name' => 'Hockey']);

        $admin = User::factory()->create(['sport_id' => null]);
        $admin->assignRole('admin');

        $hockeyMember = Member::factory()->create(['sport_id' => $hockey->id]);

        $this->actingAs($admin)
            ->get("/test-members/{$hockeyMember->id}")
            ->assertOk();
    }
}
