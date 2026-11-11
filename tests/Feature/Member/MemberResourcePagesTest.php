<?php

namespace Tests\Feature\Member;

use App\Filament\Resources\MemberResource\Pages\CreateMember;
use App\Filament\Resources\MemberResource\Pages\EditMember;
use App\Filament\Resources\MemberResource\Pages\ViewMember;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberResourcePagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Sport $sport;
    private FeeCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin    = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->sport    = Sport::factory()->create();
        $this->category = FeeCategory::factory()->create();
    }

    // ── CreateMember ──────────────────────────────────────────────

    public function test_create_page_renderiza_correctamente(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateMember::class)
            ->assertSuccessful();
    }

    public function test_create_page_crea_socio_y_redirige(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateMember::class)
            ->fillForm([
                'name'            => 'Nuevo Socio',
                'dni'             => '55556666',
                'email'           => 'nuevo@test.com',
                'birthdate'       => '1990-05-15',
                'sport_id'        => $this->sport->id,
                'fee_category_id' => $this->category->id,
                'status'          => 'active',
                'is_minor'        => false,
                'consent'         => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('members', ['dni_hash' => hash('sha256', '55556666')]);
    }

    public function test_create_detiene_menor_sin_guardian(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateMember::class)
            ->fillForm([
                'name'            => 'Menor Sin Tutor',
                'dni'             => '44445555',
                'birthdate'       => now()->subYears(10)->format('Y-m-d'),
                'sport_id'        => $this->sport->id,
                'fee_category_id' => $this->category->id,
                'status'          => 'active',
                'is_minor'        => true,
                'consent'         => true,
                'guardians'       => [],
            ])
            ->call('create');

        $this->assertDatabaseMissing('members', ['dni_hash' => hash('sha256', '44445555')]);
    }

    // ── ViewMember ────────────────────────────────────────────────

    public function test_view_page_renderiza_correctamente(): void
    {
        $member = Member::factory()->create([
            'sport_id'        => $this->sport->id,
            'fee_category_id' => $this->category->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ViewMember::class, ['record' => $member->id])
            ->assertSuccessful()
            ->assertSeeText($member->name);
    }

    // ── EditMember ────────────────────────────────────────────────

    public function test_edit_page_renderiza_correctamente(): void
    {
        $member = Member::factory()->active()->create([
            'sport_id'        => $this->sport->id,
            'fee_category_id' => $this->category->id,
            'is_minor'        => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditMember::class, ['record' => $member->id])
            ->assertSuccessful();
    }

    public function test_edit_page_guarda_cambios(): void
    {
        $member = Member::factory()->active()->create([
            'sport_id'        => $this->sport->id,
            'fee_category_id' => $this->category->id,
            'is_minor'        => false,
            'status'          => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditMember::class, ['record' => $member->id])
            ->fillForm(['status' => 'inactive'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'inactive']);
    }
}
