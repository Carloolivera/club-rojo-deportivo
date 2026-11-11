<?php

namespace Tests\Feature\Report;

use App\Filament\Pages\ReportsPage;
use App\Models\Fee;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ── Autorización ──────────────────────────────────────────────────

    public function test_admin_puede_acceder_a_reports_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(ReportsPage::getUrl())
            ->assertSuccessful();
    }

    public function test_authority_puede_acceder_a_reports_page(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $this->actingAs($authority)
            ->get(ReportsPage::getUrl())
            ->assertSuccessful();
    }

    public function test_manager_no_puede_acceder_a_reports_page(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)
            ->get(ReportsPage::getUrl())
            ->assertForbidden();
    }

    // ── Renderizado ───────────────────────────────────────────────────

    public function test_page_renderiza_sin_errores(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(ReportsPage::class)
            ->assertSuccessful();
    }

    // ── Datos por período ─────────────────────────────────────────────

    public function test_page_muestra_socios_activos_e_inactivos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $sport = Sport::factory()->create();
        Member::factory()->count(6)->create(['sport_id' => $sport->id, 'status' => 'active']);
        Member::factory()->count(2)->create(['sport_id' => $sport->id, 'status' => 'inactive']);

        Livewire::test(ReportsPage::class)
            ->assertSee('6')
            ->assertSee('2');
    }

    public function test_page_muestra_ingresos_del_periodo_seleccionado(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $period = now()->format('Y-m');
        Fee::factory()->paid()->create(['period' => $period, 'amount' => 3000.00]);
        Fee::factory()->paid()->create(['period' => $period, 'amount' => 1500.00]);

        Livewire::test(ReportsPage::class)
            ->assertSee('4.500');
    }

    public function test_filtro_de_periodo_cambia_ingresos_mostrados(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $currentPeriod = now()->format('Y-m');
        $prevPeriod    = now()->subMonth()->format('Y-m');

        Fee::factory()->paid()->create(['period' => $currentPeriod, 'amount' => 5000.00]);
        Fee::factory()->paid()->create(['period' => $prevPeriod,    'amount' => 2000.00]);

        Livewire::test(ReportsPage::class)
            ->set('selectedPeriod', $prevPeriod)
            ->assertSee('2.000')
            ->assertDontSee('5.000');
    }
}
