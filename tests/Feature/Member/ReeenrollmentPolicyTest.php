<?php

namespace Tests\Feature\Member;

use App\Models\Reenrollment;
use App\Models\User;
use App\Policies\ReeenrollmentPolicy;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReeenrollmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ReeenrollmentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->policy = new ReeenrollmentPolicy();
    }

    public function test_admin_puede_aprobar_reenrollment_pendiente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $reenrollment = Reenrollment::factory()->create(['status' => 'pending']);

        $this->assertTrue($this->policy->approve($admin, $reenrollment));
    }

    public function test_admin_no_puede_aprobar_reenrollment_ya_aprobado(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $reenrollment = Reenrollment::factory()->approved()->create();

        $this->assertFalse($this->policy->approve($admin, $reenrollment));
    }

    public function test_admin_no_puede_aprobar_reenrollment_ya_rechazado(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $reenrollment = Reenrollment::factory()->rejected()->create();

        $this->assertFalse($this->policy->approve($admin, $reenrollment));
    }

    public function test_admin_puede_rechazar_reenrollment_pendiente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $reenrollment = Reenrollment::factory()->create(['status' => 'pending']);

        $this->assertTrue($this->policy->reject($admin, $reenrollment));
    }

    public function test_admin_no_puede_rechazar_reenrollment_ya_rechazado(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $reenrollment = Reenrollment::factory()->rejected()->create();

        $this->assertFalse($this->policy->reject($admin, $reenrollment));
    }

    public function test_manager_no_puede_aprobar_reenrollment_pendiente(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $reenrollment = Reenrollment::factory()->create(['status' => 'pending']);

        $this->assertFalse($this->policy->approve($manager, $reenrollment));
    }

    public function test_manager_no_puede_rechazar_reenrollment_pendiente(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $reenrollment = Reenrollment::factory()->create(['status' => 'pending']);

        $this->assertFalse($this->policy->reject($manager, $reenrollment));
    }

    public function test_authority_no_puede_aprobar_ni_rechazar(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $reenrollment = Reenrollment::factory()->create(['status' => 'pending']);

        $this->assertFalse($this->policy->approve($authority, $reenrollment));
        $this->assertFalse($this->policy->reject($authority, $reenrollment));
    }

    public function test_solo_admin_puede_ver_lista_de_reenrollments(): void
    {
        $admin    = User::factory()->create();
        $manager  = User::factory()->create();
        $authority = User::factory()->create();

        $admin->assignRole('admin');
        $manager->assignRole('manager');
        $authority->assignRole('authority');

        $reenrollment = Reenrollment::factory()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertFalse($this->policy->viewAny($manager));
        $this->assertFalse($this->policy->viewAny($authority));

        $this->assertTrue($this->policy->view($admin, $reenrollment));
        $this->assertFalse($this->policy->view($manager, $reenrollment));
        $this->assertFalse($this->policy->view($authority, $reenrollment));
    }
}
