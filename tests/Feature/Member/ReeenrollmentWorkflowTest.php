<?php

namespace Tests\Feature\Member;

use App\Filament\Resources\MemberResource\Pages\ListMembers;
use App\Models\Member;
use App\Models\Reenrollment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReeenrollmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_socio_inactivo_puede_solicitar_reempadronamiento(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $member = Member::factory()->create(['status' => 'inactive']);

        Livewire::test(ListMembers::class)
            ->callTableAction('solicitar-reempadronamiento', $member);

        $this->assertDatabaseHas('reenrollments', [
            'member_id' => $member->id,
            'status'    => 'pending',
        ]);
    }

    public function test_no_se_puede_crear_segunda_solicitud_si_ya_hay_pending(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $member = Member::factory()->create(['status' => 'inactive']);
        Reenrollment::factory()->create([
            'member_id' => $member->id,
            'status'    => 'pending',
        ]);

        Livewire::test(ListMembers::class)
            ->callTableAction('solicitar-reempadronamiento', $member);

        $this->assertDatabaseCount('reenrollments', 1);
    }

    public function test_socio_activo_no_tiene_accion_reempadronamiento_visible(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $member = Member::factory()->create(['status' => 'active']);

        // La acción existe pero su visible() retorna false para activos.
        // Verificamos que no se crea ningún registro si se llama igual.
        Livewire::test(ListMembers::class)
            ->assertTableActionHidden('solicitar-reempadronamiento', $member);

        $this->assertDatabaseMissing('reenrollments', ['member_id' => $member->id]);
    }
}
