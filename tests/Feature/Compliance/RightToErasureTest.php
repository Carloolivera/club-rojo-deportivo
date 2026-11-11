<?php

namespace Tests\Feature\Compliance;

use App\Models\Fee;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RightToErasureTest extends TestCase
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

    public function test_admin_puede_anonimizar_socio(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $member->dni = '30111222';
        $member->save();

        $response = $this->actingAs($this->admin())
            ->deleteJson("/socios/{$member->id}/datos");

        $response->assertStatus(200)->assertJson(['ok' => true]);

        $member->refresh();
        $this->assertEquals('ANONIMIZADO', $member->name);
        $this->assertNull($member->email);
        $this->assertNull($member->phone);
        $this->assertNull($member->address);
    }

    public function test_anonimizacion_conserva_historial_de_cuotas(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        foreach (['2025-01', '2025-02', '2025-03'] as $period) {
            Fee::factory()->create(['member_id' => $member->id, 'status' => 'paid', 'period' => $period]);
        }
        Fee::factory()->create(['member_id' => $member->id, 'status' => 'overdue', 'period' => '2025-04']);

        $this->actingAs($this->admin())
            ->deleteJson("/socios/{$member->id}/datos")
            ->assertStatus(200);

        $this->assertEquals(4, Fee::where('member_id', $member->id)->count());
    }

    public function test_anonimizacion_invalida_dni_hash(): void
    {
        $member = Member::factory()->create();
        $member->dni = '30111222';
        $member->save();
        $originalHash = $member->fresh()->getRawOriginal('dni_hash');

        $this->actingAs($this->admin())
            ->deleteJson("/socios/{$member->id}/datos");

        $newHash = $member->fresh()->getRawOriginal('dni_hash');
        $this->assertNotEquals($originalHash, $newHash);
        // el hash ya no corresponde al DNI original → portal no lo encuentra
        $this->assertNotEquals(\App\Support\Dni::hash((string) '30111222'), $newHash);
    }

    public function test_manager_no_puede_anonimizar(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->manager())
            ->deleteJson("/socios/{$member->id}/datos")
            ->assertStatus(403);
    }

    public function test_unauthenticated_no_puede_anonimizar(): void
    {
        $member = Member::factory()->create();

        $this->deleteJson("/socios/{$member->id}/datos")
            ->assertStatus(401);
    }

    public function test_anonimizar_socio_inexistente_retorna_404(): void
    {
        $this->actingAs($this->admin())
            ->deleteJson('/socios/9999/datos')
            ->assertStatus(404);
    }
}
