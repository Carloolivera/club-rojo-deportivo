<?php

namespace Tests\Feature\Landing;

use App\Mail\ContactInquiry;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LandingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_landing_page_returns_200_with_landing_view(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('landing');
    }

    public function test_landing_only_receives_active_sports(): void
    {
        Sport::factory()->create(['name' => 'Fútbol', 'is_active' => true]);
        Sport::factory()->create(['name' => 'Inactivo', 'is_active' => false]);

        $response = $this->get('/');

        $response->assertViewHas('sports');
        $sports = $response->viewData('sports');
        $this->assertCount(1, $sports);
        $this->assertEquals('Fútbol', $sports->first()->name);
    }

    public function test_contact_inquiry_mailable_has_correct_subject_and_content(): void
    {
        $data = ['name' => 'Juan García', 'phone' => '+54 9 11 123456', 'sport' => 'Fútbol'];
        $mailable = new ContactInquiry($data);

        $mailable->assertHasTo(config('club.contact_email'));
        $mailable->assertHasSubject('Nueva consulta de socio — Juan García');
        $mailable->assertSeeInHtml('Juan García');
        $mailable->assertSeeInHtml('+54 9 11 123456');
        $mailable->assertSeeInHtml('Fútbol');
    }

    public function test_contact_requires_name(): void
    {
        $response = $this->postJson('/contacto', ['phone' => '123456789']);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_contact_requires_phone(): void
    {
        $response = $this->postJson('/contacto', ['name' => 'Juan']);

        $response->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }

    public function test_contact_sends_email_on_valid_data(): void
    {
        Mail::fake();

        $response = $this->postJson('/contacto', [
            'name'  => 'Juan García',
            'phone' => '+54 9 11 123456',
            'sport' => 'Fútbol',
        ]);

        $response->assertStatus(200)->assertJson(['ok' => true]);
        Mail::assertSent(ContactInquiry::class, function ($mail) {
            return $mail->hasTo(config('club.contact_email'))
                && $mail->data['name'] === 'Juan García'
                && $mail->data['sport'] === 'Fútbol';
        });
    }

    public function test_contact_is_rate_limited_after_5_requests(): void
    {
        Mail::fake();
        $data = ['name' => 'Test User', 'phone' => '123456789'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/contacto', $data)->assertStatus(200);
        }

        $this->postJson('/contacto', $data)->assertStatus(429);
    }

    public function test_landing_renders_without_errors_when_sports_empty(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Club Rojo Deportivo');
        $response->assertSee('Más disciplinas');
    }

    public function test_landing_renders_sports_from_db(): void
    {
        Sport::factory()->create(['name' => 'Fútbol', 'is_active' => true]);
        Sport::factory()->create(['name' => 'Tenis',  'is_active' => true]);
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Fútbol');
        $response->assertSee('Tenis');
        $response->assertSee('Más disciplinas'); // 2 deportes < 5
    }

    public function test_landing_hides_add_card_when_5_or_more_sports(): void
    {
        Sport::factory()->count(5)->sequence(
            ['name' => 'Fútbol',  'is_active' => true],
            ['name' => 'Tenis',   'is_active' => true],
            ['name' => 'Vóley',   'is_active' => true],
            ['name' => 'Karate',  'is_active' => true],
            ['name' => 'Básquet', 'is_active' => true],
        )->create();
        $response = $this->get('/');
        $response->assertDontSee('Más disciplinas');
    }

    public function test_landing_does_not_show_inactive_sports(): void
    {
        Sport::factory()->create(['name' => 'Polo', 'is_active' => false]);
        $response = $this->get('/');
        $response->assertDontSee('Polo');
    }
}
