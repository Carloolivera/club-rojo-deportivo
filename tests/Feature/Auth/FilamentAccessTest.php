<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_user_without_role_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_manager_can_access_admin_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_authority_can_access_admin_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('authority');

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful();
    }
}
