<?php

namespace Tests\Feature\Report;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Filament\Resources\FeeResource\Pages\ListFees;

class ListFeesExportButtonsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_ve_botones_de_export_en_list_fees(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(ListFees::class)
            ->assertSee('Exportar Excel')
            ->assertSee('Exportar PDF');
    }

    public function test_authority_ve_botones_de_export_en_list_fees(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');
        $this->actingAs($authority);

        Livewire::test(ListFees::class)
            ->assertSee('Exportar Excel')
            ->assertSee('Exportar PDF');
    }

    public function test_manager_no_ve_botones_de_export(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        Livewire::test(ListFees::class)
            ->assertDontSee('Exportar Excel')
            ->assertDontSee('Exportar PDF');
    }
}
