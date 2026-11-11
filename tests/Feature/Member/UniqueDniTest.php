<?php

namespace Tests\Feature\Member;

use App\Models\Member;
use App\Models\Sport;
use App\Rules\UniqueDni;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UniqueDniTest extends TestCase
{
    use RefreshDatabase;

    public function test_dni_passes_when_no_existing_member(): void
    {
        Sport::factory()->create();

        $validator = Validator::make(
            ['dni' => '30123456'],
            ['dni' => [new UniqueDni]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_dni_fails_when_another_member_has_same_dni(): void
    {
        Member::factory()->create(['dni' => '30123456']);

        $validator = Validator::make(
            ['dni' => '30123456'],
            ['dni' => [new UniqueDni]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('Ya existe un socio registrado con ese DNI', $validator->errors()->first('dni'));
    }

    public function test_dni_passes_when_editing_own_record(): void
    {
        $member = Member::factory()->create(['dni' => '30123456']);

        $validator = Validator::make(
            ['dni' => '30123456'],
            ['dni' => [new UniqueDni($member->id)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_dni_fails_when_editing_and_other_member_has_same_dni(): void
    {
        $member1 = Member::factory()->create(['dni' => '30123456']);
        $member2 = Member::factory()->create(['dni' => '40654321']);

        // member2 tries to use member1's DNI
        $validator = Validator::make(
            ['dni' => '30123456'],
            ['dni' => [new UniqueDni($member2->id)]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_soft_deleted_member_dni_can_be_reused(): void
    {
        $member = Member::factory()->create(['dni' => '30123456']);
        $member->delete(); // soft delete

        $validator = Validator::make(
            ['dni' => '30123456'],
            ['dni' => [new UniqueDni]]
        );

        $this->assertFalse($validator->fails());
    }
}
