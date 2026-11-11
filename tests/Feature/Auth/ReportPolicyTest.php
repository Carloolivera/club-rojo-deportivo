<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Policies\ReportPolicy;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReportPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Gate::define('view-reports', [ReportPolicy::class, 'view']);
    }

    public function test_admin_can_view_reports(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue(Gate::forUser($admin)->allows('view-reports'));
    }

    public function test_authority_can_view_reports(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $this->assertTrue(Gate::forUser($authority)->allows('view-reports'));
    }

    public function test_manager_cannot_view_reports(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->assertFalse(Gate::forUser($manager)->allows('view-reports'));
    }
}
