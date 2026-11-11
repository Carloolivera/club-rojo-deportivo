<?php

namespace Tests\Feature\Report;

use App\Filament\Widgets\MembersPerSportWidget;
use App\Filament\Widgets\MonthlyFeesWidget;
use App\Models\Fee;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ── MembersPerSportWidget ──────────────────────────────────────

    public function test_admin_puede_ver_members_per_sport_widget(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->assertTrue(MembersPerSportWidget::canView());
    }

    public function test_authority_puede_ver_members_per_sport_widget(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');
        $this->actingAs($authority);

        $this->assertTrue(MembersPerSportWidget::canView());
    }

    public function test_manager_no_puede_ver_members_per_sport_widget(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->assertFalse(MembersPerSportWidget::canView());
    }

    public function test_members_per_sport_widget_renderiza_sin_errores_para_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(MembersPerSportWidget::class)
            ->assertSuccessful();
    }

    public function test_widget_cuenta_socios_activos_por_deporte_excluyendo_inactivos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $sport = Sport::factory()->create(['name' => 'Natación']);
        Member::factory()->count(3)->create(['sport_id' => $sport->id, 'status' => 'active']);
        Member::factory()->create(['sport_id' => $sport->id, 'status' => 'inactive']);

        Livewire::test(MembersPerSportWidget::class)
            ->assertSee('Natación')
            ->assertSee('3');
    }

    // ── MonthlyFeesWidget ──────────────────────────────────────────

    public function test_admin_puede_ver_monthly_fees_widget(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->assertTrue(MonthlyFeesWidget::canView());
    }

    public function test_authority_puede_ver_monthly_fees_widget(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');
        $this->actingAs($authority);

        $this->assertTrue(MonthlyFeesWidget::canView());
    }

    public function test_manager_no_puede_ver_monthly_fees_widget(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->assertFalse(MonthlyFeesWidget::canView());
    }

    public function test_monthly_fees_widget_renderiza_sin_errores_para_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(MonthlyFeesWidget::class)
            ->assertSuccessful();
    }

    public function test_widget_muestra_conteo_correcto_de_cuotas_del_mes_por_estado(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $currentPeriod = now()->format('Y-m');
        $otherPeriod   = now()->subMonth()->format('Y-m');

        Fee::factory()->pending()->create(['period' => $currentPeriod]);
        Fee::factory()->overdue()->count(2)->create(['period' => $currentPeriod]);
        Fee::factory()->paid()->create(['period' => $otherPeriod]);

        Livewire::test(MonthlyFeesWidget::class)
            ->assertSeeInOrder(['Pendientes', '1'])
            ->assertSeeInOrder(['Vencidas', '2']);
    }
}
