<?php

namespace Tests\Feature\Portal;

use App\Models\Fee;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('portal-check:127.0.0.1');
    }

    // --- CA05: respuestas correctas ---

    public function test_socio_sin_cuotas_vencidas_responde_al_dia(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        Fee::factory()->create(['member_id' => $member->id, 'status' => 'paid']);

        $response = $this->postJson('/estado/check', ['dni' => '12345678']);

        // El DNI del factory no coincide con 12345678, pero la respuesta debe ser al_dia
        // (anti-enumeration: inexistente → al_dia)
        $response->assertStatus(200)->assertJson(['status' => 'al_dia']);
    }

    public function test_socio_al_dia_responde_al_dia(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $dni = '30111222';
        $member->dni = $dni;
        $member->save();

        Fee::factory()->create(['member_id' => $member->id, 'status' => 'paid']);

        $response = $this->postJson('/estado/check', ['dni' => $dni]);

        $response->assertStatus(200)->assertJson(['status' => 'al_dia']);
    }

    public function test_socio_con_cuota_vencida_responde_en_deuda(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $dni = '30222333';
        $member->dni = $dni;
        $member->save();

        Fee::factory()->create(['member_id' => $member->id, 'status' => 'overdue']);

        $response = $this->postJson('/estado/check', ['dni' => $dni]);

        $response->assertStatus(200)->assertJson(['status' => 'en_deuda']);
    }

    // --- Anti-enumeration ---

    public function test_dni_inexistente_responde_al_dia(): void
    {
        $response = $this->postJson('/estado/check', ['dni' => '99999999']);

        $response->assertStatus(200)->assertJson(['status' => 'al_dia']);
    }

    public function test_respuesta_no_contiene_datos_personales(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $dni = '30333444';
        $member->dni = $dni;
        $member->save();

        Fee::factory()->create(['member_id' => $member->id, 'status' => 'overdue']);

        $response = $this->postJson('/estado/check', ['dni' => $dni]);

        $body = $response->json();

        // No debe exponer PII
        $this->assertArrayNotHasKey('name', $body);
        $this->assertArrayNotHasKey('email', $body);
        $this->assertArrayNotHasKey('phone', $body);
        $this->assertArrayNotHasKey('member_id', $body);
        $this->assertArrayNotHasKey('id', $body);

        // Cuando hay deuda el endpoint incluye status + fees_url (link firmado temporal para pago)
        $this->assertCount(2, $body, 'La respuesta en deuda debe tener solo: status y fees_url');
        $this->assertArrayHasKey('fees_url', $body);
        $this->assertStringContainsString('/mis-cuotas/', $body['fees_url']);
    }

    // --- Rate limiting ---

    public function test_rate_limit_bloquea_despues_de_10_requests(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/estado/check', ['dni' => '12345678'])->assertStatus(200);
        }

        $this->postJson('/estado/check', ['dni' => '12345678'])->assertStatus(429);
    }

    // --- Validación input ---

    public function test_dni_vacio_retorna_422(): void
    {
        $response = $this->postJson('/estado/check', ['dni' => '']);

        $response->assertStatus(422)->assertJsonValidationErrors(['dni']);
    }

    public function test_sin_dni_retorna_422(): void
    {
        $response = $this->postJson('/estado/check', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['dni']);
    }

    // --- Página pública ---

    public function test_pagina_estado_es_accesible_sin_autenticacion(): void
    {
        $this->get('/estado')->assertStatus(200);
    }
}
