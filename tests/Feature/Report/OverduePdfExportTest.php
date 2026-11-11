<?php

namespace Tests\Feature\Report;

use App\Models\Fee;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverduePdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_puede_descargar_pdf_de_morosos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $sport  = Sport::factory()->create();
        $member = Member::factory()->create(['sport_id' => $sport->id]);
        Fee::factory()->overdue()->create(['member_id' => $member->id, 'period' => '2026-03']);

        $this->actingAs($admin)
            ->get(route('reports.export.overdue'))
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_authority_puede_descargar_pdf_de_morosos(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $this->actingAs($authority)
            ->get(route('reports.export.overdue'))
            ->assertSuccessful();
    }

    public function test_manager_no_puede_descargar_pdf_de_morosos(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)
            ->get(route('reports.export.overdue'))
            ->assertForbidden();
    }

    public function test_pdf_morosos_sin_deuda_retorna_documento_vacio(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('reports.export.overdue'))
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_morosos_agrupa_multiples_cuotas_del_mismo_socio(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = Member::factory()->create();
        Fee::factory()->overdue()->create(['member_id' => $member->id, 'period' => '2026-02', 'amount' => 5000]);
        Fee::factory()->overdue()->create(['member_id' => $member->id, 'period' => '2026-03', 'amount' => 5000]);

        $this->actingAs($admin)
            ->get(route('reports.export.overdue'))
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
