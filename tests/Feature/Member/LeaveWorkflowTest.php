<?php

namespace Tests\Feature\Member;

use App\Models\LeaveReason;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_dar_de_baja_socio_activo_cambia_status_a_inactive(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = Member::factory()->create(['status' => 'active']);
        $reason = LeaveReason::factory()->create();

        $member->deactivate($admin, $reason->id, 'Baja voluntaria');

        $this->assertDatabaseHas('members', [
            'id'     => $member->id,
            'status' => 'inactive',
        ]);
    }

    public function test_dar_de_baja_registra_actividad_en_log(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = Member::factory()->create(['status' => 'active']);
        $reason = LeaveReason::factory()->create();

        $member->deactivate($admin, $reason->id, 'Motivo de prueba');

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Member::class,
            'subject_id'   => $member->id,
            'causer_type'  => User::class,
            'causer_id'    => $admin->id,
            'description'  => 'Socio dado de baja',
        ]);
    }

    public function test_socio_inactivo_puede_volver_a_darse_de_baja_sin_error(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = Member::factory()->create(['status' => 'inactive']);
        $reason = LeaveReason::factory()->create();

        // El modelo no tiene guard para esto — se puede llamar, el status ya es inactive
        $member->deactivate($admin, $reason->id);

        $this->assertDatabaseHas('members', [
            'id'     => $member->id,
            'status' => 'inactive',
        ]);
    }
}
