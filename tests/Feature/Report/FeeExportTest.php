<?php

namespace Tests\Feature\Report;

use App\Exports\FeesExport;
use App\Models\Fee;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class FeeExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_fees_export_coleccion_incluye_cuotas_del_periodo(): void
    {
        $period = '2026-04';
        $sport  = Sport::factory()->create();
        $member = Member::factory()->create(['sport_id' => $sport->id]);

        Fee::factory()->create(['member_id' => $member->id, 'period' => $period]);
        Fee::factory()->create(['member_id' => $member->id, 'period' => '2026-03']);

        $export     = new FeesExport($period);
        $collection = $export->collection();

        $this->assertCount(1, $collection);
        $this->assertEquals($period, $collection->first()->period);
    }

    public function test_fees_export_headings_correctos(): void
    {
        $export = new FeesExport('2026-04');

        $this->assertEquals(
            ['Socio', 'DNI', 'Email', 'Deporte', 'Período', 'Monto ($)', 'Estado', 'Pagado el'],
            $export->headings()
        );
    }

    public function test_fees_export_map_retorna_datos_correctos(): void
    {
        $sport  = Sport::factory()->create(['name' => 'Fútbol']);
        $member = Member::factory()->create(['sport_id' => $sport->id]);
        $fee    = Fee::factory()->paid()->create([
            'member_id' => $member->id,
            'period'    => '2026-04',
            'amount'    => 5000,
        ]);

        $export = new FeesExport('2026-04');
        $row    = $export->map($fee);

        $this->assertEquals($member->name, $row[0]);
        $this->assertEquals('Fútbol', $row[3]);
        $this->assertEquals('2026-04', $row[4]);
        $this->assertEquals(5000, $row[5]);
        $this->assertEquals('Pagado', $row[6]);
        $this->assertEquals($fee->paid_at->format('d/m/Y'), $row[7]);
    }

    public function test_admin_puede_descargar_excel(): void
    {
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('fees.export.excel', ['period' => '2026-04']))
            ->assertSuccessful();

        Excel::assertDownloaded('cuotas-2026-04.xlsx');
    }

    public function test_manager_no_puede_descargar_excel(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)
            ->get(route('fees.export.excel', ['period' => '2026-04']))
            ->assertForbidden();
    }
}
