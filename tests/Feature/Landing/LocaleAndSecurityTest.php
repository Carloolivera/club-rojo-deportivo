<?php

namespace Tests\Feature\Landing;

use App\Support\Dni;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_is_in_spanish_by_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="es">', false)
            ->assertSee('Quiero ser socio');
    }

    public function test_switching_to_english_translates_the_landing(): void
    {
        $this->get('/idioma/en')->assertRedirect();

        $this->get('/')
            ->assertSee('<html lang="en">', false)
            ->assertSee('Become a member')
            ->assertDontSee('Quiero ser socio');
    }

    public function test_switching_to_portuguese_translates_the_dni_portal(): void
    {
        $this->get('/idioma/pt');

        $this->get('/estado')->assertSee('Consulte a situação das suas mensalidades');
    }

    public function test_unknown_language_is_rejected(): void
    {
        $this->get('/idioma/fr')->assertNotFound();
        $this->get('/idioma/..%2F..%2Fconfig')->assertNotFound();
    }

    public function test_language_switch_never_redirects_to_another_site(): void
    {
        $this->get('/idioma/en', ['referer' => 'https://evil.example/phish'])
            ->assertRedirect(route('landing'));
    }

    public function test_language_switch_returns_to_the_page_you_were_on(): void
    {
        $this->get('/idioma/en', ['referer' => url('/estado')])
            ->assertRedirect(url('/estado'));
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_dni_fingerprint_is_keyed_not_plain_sha256(): void
    {
        $this->assertNotSame(hash('sha256', '30111222'), Dni::hash('30111222'));
        $this->assertSame(Dni::hash('30.111.222'), Dni::hash('30111222'));
    }

    public function test_dni_portal_rejects_input_that_is_not_a_dni(): void
    {
        $this->postJson('/estado/check', ['dni' => "1' OR '1'='1"])
            ->assertStatus(422);
    }

    public function test_admin_entrance_is_hidden_behind_the_crest(): void
    {
        $this->get('/')
            ->assertDontSee('href="/admin"', false)
            ->assertSee('id="crest" data-admin="/admin"', false);
    }
}
