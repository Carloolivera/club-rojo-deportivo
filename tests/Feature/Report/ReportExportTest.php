<?php

namespace Tests\Feature\Report;

use App\Exports\MembersReportExport;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ── Clase export ──────────────────────────────────────────────────

    public function test_export_coleccion_incluye_todos_los_socios(): void
    {
        $sport = Sport::factory()->create();
        Member::factory()->count(3)->create(['sport_id' => $sport->id, 'status' => 'active']);
        Member::factory()->count(2)->create(['sport_id' => $sport->id, 'status' => 'inactive']);

        $export = new MembersReportExport();
        $this->assertCount(5, $export->collection());
    }

    public function test_export_headings_correctos(): void
    {
        $export = new MembersReportExport();

        $this->assertEquals(
            ['Nombre', 'DNI', 'Email', 'Teléfono', 'Deporte', 'Estado', 'Fecha alta'],
            $export->headings()
        );
    }

    public function test_export_map_retorna_datos_correctos(): void
    {
        $sport  = Sport::factory()->create(['name' => 'Natación']);
        $member = Member::factory()->create([
            'sport_id'  => $sport->id,
            'status'    => 'active',
        ]);

        $export = new MembersReportExport();
        $row    = $export->map($member);

        $this->assertEquals($member->name, $row[0]);
        $this->assertEquals('Natación', $row[4]);
        $this->assertEquals('Activo', $row[5]);
    }

    // ── Ruta de descarga ──────────────────────────────────────────────

    public function test_admin_puede_descargar_excel_de_reporte(): void
    {
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('reports.export.excel'))
            ->assertSuccessful();

        Excel::assertDownloaded('reporte-socios.xlsx');
    }

    public function test_authority_puede_descargar_excel_de_reporte(): void
    {
        Excel::fake();

        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $this->actingAs($authority)
            ->get(route('reports.export.excel'))
            ->assertSuccessful();

        Excel::assertDownloaded('reporte-socios.xlsx');
    }

    public function test_manager_no_puede_descargar_excel_de_reporte(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)
            ->get(route('reports.export.excel'))
            ->assertForbidden();
    }
}
