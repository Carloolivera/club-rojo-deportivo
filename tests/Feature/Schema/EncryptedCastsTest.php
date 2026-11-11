<?php

namespace Tests\Feature\Schema;

use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\Member;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptedCastsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_dni_is_encrypted_in_database(): void
    {
        $sport = Sport::factory()->create();
        $category = FeeCategory::factory()->create();

        $member = Member::create([
            'name' => 'Juan Pérez',
            'dni' => '30123456',
            'sport_id' => $sport->id,
            'fee_category_id' => $category->id,
            'status' => 'active',
            'consent_at' => now(),
            'consent_ip' => '127.0.0.1',
        ]);

        $raw = DB::table('members')->where('id', $member->id)->first();

        $this->assertNotEquals('30123456', $raw->dni, 'DNI should be encrypted in database');
        $this->assertEquals('30123456', $member->fresh()->dni, 'DNI should decrypt correctly');
    }

    public function test_member_dni_hash_is_computed_automatically(): void
    {
        $sport = Sport::factory()->create();
        $category = FeeCategory::factory()->create();

        $member = Member::create([
            'name' => 'Ana García',
            'dni' => '28456789',
            'sport_id' => $sport->id,
            'fee_category_id' => $category->id,
            'status' => 'active',
            'consent_at' => now(),
            'consent_ip' => '127.0.0.1',
        ]);

        $expected = hash('sha256', '28456789');
        $this->assertEquals($expected, $member->fresh()->dni_hash);
    }

    public function test_member_name_is_encrypted_in_database(): void
    {
        $sport = Sport::factory()->create();
        $category = FeeCategory::factory()->create();

        $member = Member::create([
            'name' => 'Carlos López',
            'dni' => '25789012',
            'sport_id' => $sport->id,
            'fee_category_id' => $category->id,
            'status' => 'active',
            'consent_at' => now(),
            'consent_ip' => '127.0.0.1',
        ]);

        $raw = DB::table('members')->where('id', $member->id)->first();

        $this->assertNotEquals('Carlos López', $raw->name);
        $this->assertEquals('Carlos López', $member->fresh()->name);
    }

    public function test_guardian_name_is_encrypted_in_database(): void
    {
        $guardian = Guardian::create([
            'name' => 'María Tutora',
            'phone' => '11-123456',
            'relationship' => 'madre',
        ]);

        $raw = DB::table('guardians')->where('id', $guardian->id)->first();

        $this->assertNotEquals('María Tutora', $raw->name);
        $this->assertEquals('María Tutora', $guardian->fresh()->name);
    }

    public function test_guardian_optional_dni_encrypts_when_present(): void
    {
        $guardian = Guardian::create([
            'name' => 'Pedro Tutor',
            'dni' => '22334455',
            'phone' => '11-654321',
            'relationship' => 'padre',
        ]);

        $raw = DB::table('guardians')->where('id', $guardian->id)->first();

        $this->assertNotEquals('22334455', $raw->dni);
        $this->assertEquals('22334455', $guardian->fresh()->dni);
    }

    public function test_guardian_null_dni_stays_null(): void
    {
        $guardian = Guardian::create([
            'name' => 'Sin DNI',
            'phone' => '11-000000',
            'relationship' => 'tutor',
        ]);

        $this->assertNull($guardian->fresh()->dni);
    }

    public function test_member_lookup_by_dni_hash(): void
    {
        $sport = Sport::factory()->create();
        $category = FeeCategory::factory()->create();

        Member::create([
            'name' => 'Buscar Este',
            'dni' => '31111111',
            'sport_id' => $sport->id,
            'fee_category_id' => $category->id,
            'status' => 'active',
            'consent_at' => now(),
            'consent_ip' => '127.0.0.1',
        ]);

        $found = Member::where('dni_hash', hash('sha256', '31111111'))->first();

        $this->assertNotNull($found);
        $this->assertEquals('31111111', $found->dni);
    }
}
