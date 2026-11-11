<?php

namespace Tests\Feature\Compliance;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptedCastsTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_se_guarda_cifrado_en_db(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $member->name = 'Juan Pérez';
        $member->save();

        $raw = DB::table('members')->where('id', $member->id)->value('name');
        $this->assertNotEquals('Juan Pérez', $raw, 'name debe estar cifrado en DB');
        $this->assertNotEmpty($raw);
        // El valor cifrado de Laravel contiene el payload JSON cifrado — no puede leerse en claro
        $this->assertStringNotContainsString('Juan', $raw);
    }

    public function test_email_se_guarda_cifrado_en_db(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $member->email = 'juan@example.com';
        $member->save();

        $raw = DB::table('members')->where('id', $member->id)->value('email');
        $this->assertNotNull($raw);
        $this->assertNotEquals('juan@example.com', $raw);
    }

    public function test_dni_se_guarda_cifrado_y_hash_correcto(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $member->dni = '30111222';
        $member->save();

        $row = DB::table('members')->where('id', $member->id)->first();
        $this->assertNotEquals('30111222', $row->dni, 'DNI debe estar cifrado');
        $this->assertEquals(\App\Support\Dni::hash((string) '30111222'), $row->dni_hash, 'dni_hash debe ser la huella HMAC del DNI');
    }

    public function test_model_desencripta_automaticamente(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $member->name = 'Carlos Olivera';
        $member->email = 'carlo@test.com';
        $member->dni = '29999888';
        $member->save();

        $fresh = Member::find($member->id);
        $this->assertEquals('Carlos Olivera', $fresh->name);
        $this->assertEquals('carlo@test.com', $fresh->email);
        $this->assertEquals('29999888', $fresh->dni);
    }
}
