<?php

namespace Tests\Feature\Report;

use App\Filament\Widgets\KpiStatsWidget;
use App\Models\Fee;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KpiStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ── Autorización ──────────────────────────────────────────────────

    public function test_admin_puede_ver_kpi_widget(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->assertTrue(KpiStatsWidget::canView());
    }

    public function test_authority_puede_ver_kpi_widget(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');
        $this->actingAs($authority);

        $this->assertTrue(KpiStatsWidget::canView());
    }

    public function test_manager_no_puede_ver_kpi_widget(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->assertFalse(KpiStatsWidget::canView());
    }

    // ── Renderizado ───────────────────────────────────────────────────

    public function test_kpi_widget_renderiza_sin_errores(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(KpiStatsWidget::class)
            ->assertSuccessful();
    }

    // ── Conteo socios activos ─────────────────────────────────────────

    public function test_widget_muestra_socios_activos_correctamente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $sport = Sport::factory()->create();
        Member::factory()->count(4)->create(['sport_id' => $sport->id, 'status' => 'active']);
        Member::factory()->count(2)->create(['sport_id' => $sport->id, 'status' => 'inactive']);

        Livewire::test(KpiStatsWidget::class)
            ->assertSee('4');
    }

    // ── Conteo socios inactivos ───────────────────────────────────────

    public function test_widget_muestra_socios_inactivos_correctamente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $sport = Sport::factory()->create();
        Member::factory()->count(3)->create(['sport_id' => $sport->id, 'status' => 'active']);
        Member::factory()->count(5)->create(['sport_id' => $sport->id, 'status' => 'inactive']);

        Livewire::test(KpiStatsWidget::class)
            ->assertSee('5');
    }

    // ── Ingresos del mes ──────────────────────────────────────────────

    public function test_widget_muestra_ingresos_del_mes_correctamente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $currentPeriod = now()->format('Y-m');
        $prevPeriod    = now()->subMonth()->format('Y-m');

        Fee::factory()->paid()->create(['period' => $currentPeriod, 'amount' => 1500.00]);
        Fee::factory()->paid()->create(['period' => $currentPeriod, 'amount' => 2500.00]);
        Fee::factory()->paid()->create(['period' => $prevPeriod,    'amount' => 9999.00]);

        Livewire::test(KpiStatsWidget::class)
            ->assertSee('4.000');
    }

    public function test_widget_excluye_cuotas_no_pagadas_del_ingreso(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $currentPeriod = now()->format('Y-m');
        Fee::factory()->pending()->create(['period' => $currentPeriod, 'amount' => 5000.00]);
        Fee::factory()->paid()->create(['period' => $currentPeriod, 'amount' => 1000.00]);

        Livewire::test(KpiStatsWidget::class)
            ->assertSee('1.000');
    }
}
