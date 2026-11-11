<?php

namespace Tests\Feature\Report;

use App\Models\Fee;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeePdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_puede_descargar_pdf(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $sport  = Sport::factory()->create();
        $member = Member::factory()->create(['sport_id' => $sport->id]);
        Fee::factory()->paid()->create(['member_id' => $member->id, 'period' => '2026-04']);

        $response = $this->actingAs($admin)
            ->get(route('fees.export.pdf', ['period' => '2026-04']));

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_authority_puede_descargar_pdf(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $response = $this->actingAs($authority)
            ->get(route('fees.export.pdf', ['period' => '2026-04']));

        $response->assertSuccessful();
    }

    public function test_manager_no_puede_descargar_pdf(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)
            ->get(route('fees.export.pdf', ['period' => '2026-04']))
            ->assertForbidden();
    }
}
