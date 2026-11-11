<?php

namespace Tests\Feature\Communication;

use App\Mail\ClubBroadcast;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ClubBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_admin_puede_enviar_broadcast_a_socios_activos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Member::factory()->create(['status' => 'active', 'email' => 'socio1@test.com']);
        Member::factory()->create(['status' => 'active', 'email' => 'socio2@test.com']);
        Member::factory()->create(['status' => 'inactive', 'email' => 'inactivo@test.com']);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\BroadcastPage::class)
            ->set('subject', 'Novedades del club')
            ->set('body', 'Mensaje de prueba con contenido suficiente.')
            ->call('send');

        Mail::assertSent(ClubBroadcast::class, 2);
    }

    public function test_broadcast_no_incluye_socios_sin_email(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Member::factory()->create(['status' => 'active', 'email' => 'conmail@test.com']);
        Member::factory()->create(['status' => 'active', 'email' => null]);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\BroadcastPage::class)
            ->set('subject', 'Novedades del club')
            ->set('body', 'Mensaje de prueba con contenido suficiente.')
            ->call('send');

        Mail::assertSent(ClubBroadcast::class, 1);
    }

    public function test_broadcast_valida_campos_requeridos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\BroadcastPage::class)
            ->set('subject', '')
            ->set('body', '')
            ->call('send')
            ->assertHasErrors(['subject', 'body']);

        Mail::assertNothingSent();
    }

    public function test_manager_no_puede_acceder_a_broadcast_page(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)
            ->get('/admin/broadcast')
            ->assertForbidden();
    }

    public function test_mailable_broadcast_tiene_subject_correcto(): void
    {
        $mail = new ClubBroadcast('Asunto de prueba', 'Cuerpo del mensaje.');
        $this->assertSame('Asunto de prueba', $mail->envelope()->subject);
    }
}
