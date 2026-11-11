<?php

namespace Tests\Feature\Schema;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\Member;
use App\Models\Sport;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sport_factory_creates_valid_record(): void
    {
        $sport = Sport::factory()->create();

        $this->assertDatabaseHas('sports', ['id' => $sport->id]);
        $this->assertTrue($sport->is_active);
    }

    public function test_fee_category_factory_creates_valid_record(): void
    {
        $category = FeeCategory::factory()->create();

        $this->assertDatabaseHas('fee_categories', ['id' => $category->id]);
        $this->assertGreaterThan(0, $category->amount);
    }

    public function test_member_factory_creates_valid_record(): void
    {
        $member = Member::factory()->create();

        $this->assertDatabaseHas('members', ['id' => $member->id]);
        $this->assertNotNull($member->dni);
        $this->assertNotNull($member->dni_hash);
        $this->assertEquals(64, strlen($member->dni_hash));
    }

    public function test_member_factory_minor_state(): void
    {
        $member = Member::factory()->minor()->create();

        $this->assertTrue($member->is_minor);
        $this->assertLessThan(18, now()->diffInYears($member->birthdate));
    }

    public function test_member_factory_active_state(): void
    {
        $member = Member::factory()->active()->create();

        $this->assertEquals('active', $member->status);
    }

    public function test_guardian_factory_creates_valid_record(): void
    {
        $guardian = Guardian::factory()->create();

        $this->assertDatabaseHas('guardians', ['id' => $guardian->id]);
        $this->assertNotEmpty($guardian->name);
        $this->assertNotEmpty($guardian->phone);
    }

    public function test_fee_factory_paid_state(): void
    {
        $fee = Fee::factory()->paid()->create();

        $this->assertEquals('paid', $fee->status);
        $this->assertNotNull($fee->paid_at);
        $this->assertNotNull($fee->payment_method);
    }

    public function test_fee_factory_overdue_state(): void
    {
        $fee = Fee::factory()->overdue()->create();

        $this->assertEquals('overdue', $fee->status);
        $this->assertNull($fee->paid_at);
    }

    public function test_fee_unique_member_period_constraint(): void
    {
        $member = Member::factory()->create();

        Fee::factory()->create(['member_id' => $member->id, 'period' => '2026-01']);

        $this->expectException(QueryException::class);

        Fee::factory()->create(['member_id' => $member->id, 'period' => '2026-01']);
    }

    public function test_member_dni_hash_is_sha256_of_dni(): void
    {
        $member = Member::factory()->create();

        $this->assertEquals(
            \App\Support\Dni::hash((string) $member->dni),
            $member->dni_hash
        );
    }
}
