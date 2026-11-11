<?php

namespace Tests\Feature\Report;

use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_puede_descargar_pdf_de_reporte(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $sport = Sport::factory()->create();
        Member::factory()->count(3)->create(['sport_id' => $sport->id]);

        $this->actingAs($admin)
            ->get(route('reports.export.pdf'))
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_authority_puede_descargar_pdf_de_reporte(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $this->actingAs($authority)
            ->get(route('reports.export.pdf'))
            ->assertSuccessful();
    }

    public function test_manager_no_puede_descargar_pdf_de_reporte(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)
            ->get(route('reports.export.pdf'))
            ->assertForbidden();
    }
}
