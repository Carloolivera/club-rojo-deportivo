<?php

namespace Tests\Feature\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sports_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('sports'));
        $this->assertTrue(Schema::hasColumns('sports', ['id', 'name', 'is_active', 'created_at', 'updated_at']));
    }

    public function test_fee_categories_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('fee_categories'));
        $this->assertTrue(Schema::hasColumns('fee_categories', ['id', 'name', 'amount', 'is_active']));
    }

    public function test_leave_reasons_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('leave_reasons'));
        $this->assertTrue(Schema::hasColumns('leave_reasons', ['id', 'name', 'is_active']));
    }

    public function test_members_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('members'));
        $this->assertTrue(Schema::hasColumns('members', [
            'id', 'name', 'dni', 'dni_hash', 'email', 'phone', 'address',
            'birthdate', 'is_minor', 'sport_id', 'fee_category_id',
            'status', 'consent_at', 'consent_ip', 'notes',
            'deleted_at', 'created_at', 'updated_at',
        ]));
    }

    public function test_guardians_table_has_soft_deletes(): void
    {
        $this->assertTrue(Schema::hasTable('guardians'));
        $this->assertTrue(Schema::hasColumn('guardians', 'deleted_at'));
    }

    public function test_member_guardian_pivot_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('member_guardian'));
        $this->assertTrue(Schema::hasColumns('member_guardian', ['member_id', 'guardian_id', 'is_primary']));
    }

    public function test_fees_table_has_mp_columns(): void
    {
        $this->assertTrue(Schema::hasTable('fees'));
        $this->assertTrue(Schema::hasColumns('fees', [
            'member_id', 'period', 'amount', 'status',
            'paid_at', 'payment_method', 'mp_preference_id', 'mp_payment_id',
            'deleted_at',
        ]));
    }

    public function test_member_periods_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('member_periods'));
        $this->assertTrue(Schema::hasColumns('member_periods', [
            'member_id', 'sport_id', 'started_at', 'ended_at', 'leave_reason_id',
        ]));
    }

    public function test_reenrollments_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('reenrollments'));
        $this->assertTrue(Schema::hasColumns('reenrollments', [
            'member_id', 'requested_at', 'approved_at', 'approved_by', 'status',
        ]));
    }

    public function test_users_table_has_sport_id(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'sport_id'));
    }
}
