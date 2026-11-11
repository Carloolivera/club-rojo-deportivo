<?php

namespace Tests\Feature\Member;

use App\Filament\Resources\LeaveReasonResource\Pages\CreateLeaveReason;
use App\Filament\Resources\LeaveReasonResource\Pages\EditLeaveReason;
use App\Filament\Resources\LeaveReasonResource\Pages\ListLeaveReasons;
use App\Models\LeaveReason;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveReasonResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_puede_listar_motivos_de_baja(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        LeaveReason::factory()->count(3)->create();

        Livewire::test(ListLeaveReasons::class)
            ->assertSuccessful();
    }

    public function test_admin_puede_crear_motivo_de_baja(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(CreateLeaveReason::class)
            ->fillForm([
                'name'      => 'Mudanza',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leave_reasons', ['name' => 'Mudanza', 'is_active' => true]);
    }

    public function test_admin_puede_editar_motivo_de_baja(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $reason = LeaveReason::factory()->create(['name' => 'Original', 'is_active' => true]);

        Livewire::test(EditLeaveReason::class, ['record' => $reason->id])
            ->fillForm([
                'name'      => 'Actualizado',
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leave_reasons', ['id' => $reason->id, 'name' => 'Actualizado', 'is_active' => false]);
    }

    public function test_manager_no_puede_acceder_a_motivos_de_baja(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->get('/admin/leave-reasons')->assertForbidden();
    }

    public function test_authority_no_puede_acceder_a_motivos_de_baja(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');
        $this->actingAs($authority);

        $this->get('/admin/leave-reasons')->assertForbidden();
    }

    public function test_nombre_duplicado_falla_validacion(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        LeaveReason::factory()->create(['name' => 'Existente']);

        Livewire::test(CreateLeaveReason::class)
            ->fillForm([
                'name'      => 'Existente',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
    }

    public function test_is_active_toggle_desactiva_motivo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $reason = LeaveReason::factory()->create(['is_active' => true]);

        Livewire::test(EditLeaveReason::class, ['record' => $reason->id])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($reason->fresh()->is_active);
    }
}
