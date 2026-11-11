<?php

namespace Tests\Feature\Member;

use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MemberPolicyViewAnyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_any_members(): void
    {
        $admin = User::factory()->create(['sport_id' => null]);
        $admin->assignRole('admin');

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Member::class));
    }

    public function test_manager_can_view_any_members(): void
    {
        $sport = Sport::factory()->create();
        $manager = User::factory()->create(['sport_id' => $sport->id]);
        $manager->assignRole('manager');

        $this->assertTrue(Gate::forUser($manager)->allows('viewAny', Member::class));
    }

    public function test_authority_cannot_view_any_members(): void
    {
        $authority = User::factory()->create(['sport_id' => null]);
        $authority->assignRole('authority');

        $this->assertFalse(Gate::forUser($authority)->allows('viewAny', Member::class));
    }

    public function test_unauthenticated_user_cannot_view_any_members(): void
    {
        $user = User::factory()->create();
        // no role assigned

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Member::class));
    }
}
