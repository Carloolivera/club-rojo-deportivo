<?php

namespace Tests\Feature\Communication;

use App\Mail\MemberOverdueReminder;
use App\Models\Fee;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class MemberOverdueReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_accion_envia_mail_a_socio_con_cuotas_vencidas(): void
    {
        $admin  = User::factory()->create();
        $admin->assignRole('admin');
        $member = Member::factory()->create(['email' => 'socio@test.com']);
        Fee::factory()->overdue()->count(2)->sequence(
            ['period' => '2025-11'],
            ['period' => '2025-12'],
        )->create(['member_id' => $member->id]);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\MemberResource\Pages\ListMembers::class)
            ->callTableAction('send-overdue-reminder', $member);

        Mail::assertSent(MemberOverdueReminder::class, fn ($mail) => $mail->hasTo('socio@test.com'));
    }

    public function test_accion_no_envia_mail_si_no_hay_cuotas_vencidas(): void
    {
        $admin  = User::factory()->create();
        $admin->assignRole('admin');
        $member = Member::factory()->create(['email' => 'socio@test.com']);
        Fee::factory()->paid()->create(['member_id' => $member->id]);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\MemberResource\Pages\ListMembers::class)
            ->callTableAction('send-overdue-reminder', $member);

        Mail::assertNotSent(MemberOverdueReminder::class);
    }

    public function test_mailable_tiene_asunto_correcto(): void
    {
        $member = Member::factory()->make(['name' => 'Juan Pérez']);
        $fees   = Fee::factory()->overdue()->count(3)->make(['member_id' => 1]);

        $mail = new MemberOverdueReminder($member, collect($fees));

        $this->assertStringContainsString('3 cuota(s) vencida(s)', $mail->envelope()->subject);
    }
}
