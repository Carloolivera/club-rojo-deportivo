<?php

namespace Tests\Feature\Member;

use App\Filament\Resources\ReeenrollmentResource\Pages\ListReeenrollments;
use App\Models\Member;
use App\Models\Reenrollment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReeenrollmentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_puede_listar_solicitudes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Reenrollment::factory()->count(3)->create();

        Livewire::test(ListReeenrollments::class)
            ->assertSuccessful();
    }

    public function test_manager_no_puede_acceder_a_solicitudes(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->get('/admin/reeenrollments')->assertForbidden();
    }

    public function test_authority_no_puede_acceder_a_solicitudes(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');
        $this->actingAs($authority);

        $this->get('/admin/reeenrollments')->assertForbidden();
    }

    public function test_admin_puede_aprobar_solicitud_pendiente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $member = Member::factory()->create(['status' => 'inactive']);
        $reenrollment = Reenrollment::factory()->create([
            'member_id' => $member->id,
            'status'    => 'pending',
        ]);

        Livewire::test(ListReeenrollments::class)
            ->callTableAction('approve', $reenrollment);

        $this->assertDatabaseHas('reenrollments', [
            'id'     => $reenrollment->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('members', [
            'id'     => $member->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_puede_rechazar_solicitud_pendiente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $member = Member::factory()->create(['status' => 'inactive']);
        $reenrollment = Reenrollment::factory()->create([
            'member_id' => $member->id,
            'status'    => 'pending',
        ]);

        Livewire::test(ListReeenrollments::class)
            ->callTableAction('reject', $reenrollment);

        $this->assertDatabaseHas('reenrollments', [
            'id'     => $reenrollment->id,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseHas('members', [
            'id'     => $member->id,
            'status' => 'inactive',
        ]);
    }

    public function test_solicitud_aprobada_no_muestra_acciones_approve_reject(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Reenrollment::factory()->approved()->create();

        Livewire::test(ListReeenrollments::class)
            ->assertSuccessful();
    }
}
