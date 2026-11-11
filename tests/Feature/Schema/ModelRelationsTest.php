<?php

namespace Tests\Feature\Schema;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\Member;
use App\Models\Sport;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ── Fee scopes ────────────────────────────────────────────────

    public function test_fee_scope_pending_filtra_por_estado(): void
    {
        Fee::factory()->pending()->create(['period' => '2025-11']);
        Fee::factory()->overdue()->create(['period' => '2025-12']);
        Fee::factory()->paid()->create(['period' => '2025-10']);

        $this->assertSame(1, Fee::pending()->count());
    }

    public function test_fee_scope_overdue_filtra_por_estado(): void
    {
        Fee::factory()->overdue()->create(['period' => '2025-11']);
        Fee::factory()->pending()->create(['period' => '2025-12']);

        $this->assertSame(1, Fee::overdue()->count());
    }

    public function test_fee_scope_for_period_filtra_por_periodo(): void
    {
        Fee::factory()->create(['period' => '2025-11']);
        Fee::factory()->create(['period' => '2025-12']);
        Fee::factory()->create(['period' => '2025-10']);

        $this->assertSame(1, Fee::forPeriod('2025-11')->count());
    }

    public function test_fee_belongs_to_member(): void
    {
        $member = Member::factory()->create();
        $fee    = Fee::factory()->create(['member_id' => $member->id, 'period' => '2025-11']);

        $this->assertInstanceOf(Member::class, $fee->member);
        $this->assertSame($member->id, $fee->member->id);
    }

    // ── FeeCategory ───────────────────────────────────────────────

    public function test_fee_category_factory_crea_registro_valido(): void
    {
        $category = FeeCategory::factory()->create(['name' => 'Infantil', 'amount' => 2000]);

        $this->assertDatabaseHas('fee_categories', ['name' => 'Infantil']);
        $this->assertTrue((bool) $category->is_active);
    }

    public function test_fee_category_is_active_cast_booleano(): void
    {
        $active   = FeeCategory::factory()->create(['is_active' => true]);
        $inactive = FeeCategory::factory()->create(['is_active' => false]);

        $this->assertTrue($active->is_active);
        $this->assertFalse($inactive->is_active);
    }

    public function test_fee_category_has_many_members(): void
    {
        $category = FeeCategory::factory()->create();
        Member::factory()->count(3)->create(['fee_category_id' => $category->id]);

        $this->assertCount(3, $category->members);
    }

    // ── Sport ─────────────────────────────────────────────────────

    public function test_sport_factory_crea_registro_valido(): void
    {
        $sport = Sport::factory()->create(['name' => 'Natación']);

        $this->assertDatabaseHas('sports', ['name' => 'Natación']);
        $this->assertTrue((bool) $sport->is_active);
    }

    public function test_sport_is_active_cast_booleano(): void
    {
        $active   = Sport::factory()->create(['is_active' => true]);
        $inactive = Sport::factory()->create(['is_active' => false]);

        $this->assertTrue($active->is_active);
        $this->assertFalse($inactive->is_active);
    }

    public function test_sport_has_many_members(): void
    {
        $sport = Sport::factory()->create();
        Member::factory()->count(2)->create(['sport_id' => $sport->id]);

        $this->assertCount(2, $sport->members);
    }

    // ── Guardian ─────────────────────────────────────────────────

    public function test_guardian_belongs_to_many_members(): void
    {
        $guardian = Guardian::factory()->create();
        $members  = Member::factory()->count(2)->create();

        $guardian->members()->attach($members->pluck('id'), ['is_primary' => true]);

        $this->assertCount(2, $guardian->members);
    }

    public function test_guardian_pivot_tiene_is_primary(): void
    {
        $guardian = Guardian::factory()->create();
        $member   = Member::factory()->create();

        $guardian->members()->attach($member->id, ['is_primary' => true]);

        $this->assertTrue((bool) $guardian->members->first()->pivot->is_primary);
    }

    public function test_guardian_soft_deletes(): void
    {
        $guardian = Guardian::factory()->create();
        $guardian->delete();

        $this->assertSoftDeleted('guardians', ['id' => $guardian->id]);
    }
}
