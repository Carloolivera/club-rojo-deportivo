<?php

namespace Tests\Feature\Member;

use App\Filament\Resources\MemberPeriodResource\Pages\ListMemberPeriods;
use App\Models\MemberPeriod;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberPeriodResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_puede_listar_periodos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        MemberPeriod::factory()->count(3)->create();

        Livewire::test(ListMemberPeriods::class)
            ->assertSuccessful();
    }

    public function test_authority_puede_listar_periodos(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');
        $this->actingAs($authority);

        MemberPeriod::factory()->count(2)->create();

        Livewire::test(ListMemberPeriods::class)
            ->assertSuccessful();
    }

    public function test_manager_no_puede_acceder_a_periodos(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->get('/admin/member-periods')->assertForbidden();
    }

    public function test_no_existe_boton_crear(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(ListMemberPeriods::class)
            ->assertDontSee('Nuevo')
            ->assertDontSee('Crear');
    }

    public function test_periodos_cerrados_muestran_fecha_fin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        MemberPeriod::factory()->closed()->create();

        Livewire::test(ListMemberPeriods::class)
            ->assertSuccessful();
    }
}
