<?php

namespace Tests\Feature\Member;

use App\Models\LeaveReason;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class MemberAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_activate_updates_status_and_logs_activity(): void
    {
        $sport = Sport::factory()->create();
        $admin = User::factory()->create(['sport_id' => null]);
        $admin->assignRole('admin');

        $member = Member::factory()->inactive()->create(['sport_id' => $sport->id]);

        $member->activate($admin);

        $this->assertEquals('active', $member->fresh()->status);

        $log = Activity::where('subject_type', Member::class)
            ->where('subject_id', $member->id)
            ->where('description', 'Socio activado')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('active', $log->properties['status']);
        $this->assertEquals($admin->id, $log->causer_id);
    }

    public function test_deactivate_updates_status_and_logs_reason(): void
    {
        $sport = Sport::factory()->create();
        $admin = User::factory()->create(['sport_id' => null]);
        $admin->assignRole('admin');
        $reason = LeaveReason::factory()->create();

        $member = Member::factory()->active()->create(['sport_id' => $sport->id]);

        $member->deactivate($admin, $reason->id, 'Se mudó de ciudad');

        $this->assertEquals('inactive', $member->fresh()->status);

        $log = Activity::where('subject_type', Member::class)
            ->where('subject_id', $member->id)
            ->where('description', 'Socio dado de baja')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('inactive', $log->properties['status']);
        $this->assertEquals($reason->id, $log->properties['reason_id']);
        $this->assertEquals($admin->id, $log->causer_id);
    }

    public function test_suspend_updates_status_and_logs_notes(): void
    {
        $sport = Sport::factory()->create();
        $admin = User::factory()->create(['sport_id' => null]);
        $admin->assignRole('admin');

        $member = Member::factory()->active()->create(['sport_id' => $sport->id]);

        $member->suspend($admin, 'Conducta inapropiada');

        $this->assertEquals('suspended', $member->fresh()->status);

        $log = Activity::where('subject_type', Member::class)
            ->where('subject_id', $member->id)
            ->where('description', 'Socio suspendido')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('suspended', $log->properties['status']);
        $this->assertEquals('Conducta inapropiada', $log->properties['notes']);
    }
}
