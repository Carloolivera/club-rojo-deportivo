<?php

namespace Tests\Feature\Compliance;

use App\Models\Fee;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RightToAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function manager(): User
    {
        return User::factory()->create()->assignRole('manager');
    }

    public function test_admin_puede_exportar_datos_socio(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $member->dni = '30111222';
        $member->save();
        Fee::factory()->count(2)->sequence(
            ['period' => '2025-01'],
            ['period' => '2025-02'],
        )->create(['member_id' => $member->id]);

        $response = $this->actingAs($this->admin())
            ->getJson("/socios/{$member->id}/exportar");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('member', $data);
        $this->assertArrayHasKey('fees', $data);
    }

    public function test_exportacion_contiene_campos_personales(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $member->dni = '30222333';
        $member->save();

        $response = $this->actingAs($this->admin())
            ->getJson("/socios/{$member->id}/exportar");

        $memberData = $response->json('member');
        $this->assertArrayHasKey('name', $memberData);
        $this->assertArrayHasKey('dni', $memberData);
        $this->assertArrayHasKey('email', $memberData);
        $this->assertArrayHasKey('birthdate', $memberData);
        $this->assertArrayHasKey('status', $memberData);
    }

    public function test_exportacion_no_contiene_claves_internas(): void
    {
        $member = Member::factory()->create();

        $response = $this->actingAs($this->admin())
            ->getJson("/socios/{$member->id}/exportar");

        $memberData = $response->json('member');
        $this->assertArrayNotHasKey('dni_hash', $memberData);
        $this->assertArrayNotHasKey('consent_ip', $memberData);
    }

    public function test_exportacion_incluye_historial_cuotas(): void
    {
        $member = Member::factory()->create();
        foreach (['2025-01', '2025-02', '2025-03'] as $period) {
            Fee::factory()->create(['member_id' => $member->id, 'status' => 'paid', 'period' => $period]);
        }

        $response = $this->actingAs($this->admin())
            ->getJson("/socios/{$member->id}/exportar");

        $this->assertCount(3, $response->json('fees'));
    }

    public function test_manager_no_puede_exportar(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->manager())
            ->getJson("/socios/{$member->id}/exportar")
            ->assertStatus(403);
    }

    public function test_unauthenticated_no_puede_exportar(): void
    {
        $member = Member::factory()->create();

        $this->getJson("/socios/{$member->id}/exportar")
            ->assertStatus(401);
    }

    public function test_socio_inexistente_retorna_404(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/socios/9999/exportar')
            ->assertStatus(404);
    }
}
