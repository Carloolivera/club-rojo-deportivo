<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_assigned_admin_role(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_user_can_be_assigned_manager_role(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $user->assignRole('manager');

        $this->assertTrue($user->hasRole('manager'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_admin_can_access_filament_panel(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->canAccessPanel(new Panel));
    }

    public function test_user_without_role_cannot_access_filament_panel(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel(new Panel));
    }

    public function test_manager_can_access_filament_panel(): void
    {
        Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('manager');

        $this->assertTrue($user->canAccessPanel(new Panel));
    }

    public function test_authority_can_access_filament_panel(): void
    {
        Role::create(['name' => 'authority', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('authority');

        $this->assertTrue($user->canAccessPanel(new Panel));
    }
}
