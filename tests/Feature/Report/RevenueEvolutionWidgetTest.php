<?php

namespace Tests\Feature\Report;

use App\Filament\Widgets\RevenueEvolutionWidget;
use App\Models\Fee;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevenueEvolutionWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_puede_ver_widget(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->assertTrue(RevenueEvolutionWidget::canView());
    }

    public function test_authority_puede_ver_widget(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');
        $this->actingAs($authority);

        $this->assertTrue(RevenueEvolutionWidget::canView());
    }

    public function test_manager_no_puede_ver_widget(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->assertFalse(RevenueEvolutionWidget::canView());
    }

    public function test_widget_renderiza_sin_errores(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(RevenueEvolutionWidget::class)
            ->assertSuccessful();
    }

    public function test_get_data_retorna_estructura_correcta(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $widget = new RevenueEvolutionWidget();
        $data   = $widget->getData();

        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('revenues', $data);
        $this->assertArrayHasKey('members', $data);
        $this->assertCount(6, $data['labels']);
        $this->assertCount(6, $data['revenues']);
        $this->assertCount(6, $data['members']);
    }

    public function test_get_data_suma_cuotas_pagadas_del_periodo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $currentPeriod = now()->format('Y-m');
        Fee::factory()->paid()->create(['period' => $currentPeriod, 'amount' => 5000]);
        Fee::factory()->paid()->create(['period' => $currentPeriod, 'amount' => 3000]);
        Fee::factory()->pending()->create(['period' => $currentPeriod, 'amount' => 2000]);

        $widget   = new RevenueEvolutionWidget();
        $data     = $widget->getData();
        $lastIdx  = count($data['revenues']) - 1;

        $this->assertSame(8000.0, $data['revenues'][$lastIdx]);
    }

    public function test_get_data_cuenta_nuevos_socios_del_periodo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Member::factory()->count(3)->create(['created_at' => now()]);

        $widget  = new RevenueEvolutionWidget();
        $data    = $widget->getData();
        $lastIdx = count($data['members']) - 1;

        $this->assertSame(3, $data['members'][$lastIdx]);
    }
}
