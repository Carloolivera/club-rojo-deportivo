<?php

namespace Tests\Feature\Fee;

use App\Mail\OverdueFeeNotification;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendOverdueFeeAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();

        // A fee is due on the first day of its period (YYYY-MM), so whether a fee
        // created "N days ago" crosses the threshold depends on the day of the month.
        // Freezing the clock mid-month makes these tests give the same result every day.
        Carbon::setTestNow(Carbon::parse('2026-03-25 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // CA02: socio moroso > 35 días → email enviado al admin
    public function test_sends_email_when_overdue_fees_exceed_threshold(): void
    {
        $this->seedRoles();
        $admin = User::factory()->create(['email' => 'admin@test.com']);
        $admin->assignRole('admin');

        $this->createOverdueFee(daysAgo: 40);

        $this->artisan('fees:send-alerts')->assertSuccessful();

        Mail::assertSent(OverdueFeeNotification::class, 1);
        Mail::assertSent(OverdueFeeNotification::class, fn ($mail) => $mail->hasTo('admin@test.com'));
    }

    public function test_does_not_send_when_no_fees_exceed_threshold(): void
    {
        $this->seedRoles();
        User::factory()->create()->assignRole('admin');

        $this->createOverdueFee(daysAgo: 20);

        $this->artisan('fees:send-alerts')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_sends_to_admin_and_manager_but_not_authority(): void
    {
        $this->seedRoles();
        $admin = User::factory()->create(['email' => 'admin@test.com']);
        $admin->assignRole('admin');
        $manager = User::factory()->create(['email' => 'manager@test.com']);
        $manager->assignRole('manager');
        $authority = User::factory()->create(['email' => 'authority@test.com']);
        $authority->assignRole('authority');

        $this->createOverdueFee(daysAgo: 40);

        $this->artisan('fees:send-alerts')->assertSuccessful();

        Mail::assertSent(OverdueFeeNotification::class, 2);
        Mail::assertSent(OverdueFeeNotification::class, fn ($m) => $m->hasTo('admin@test.com'));
        Mail::assertSent(OverdueFeeNotification::class, fn ($m) => $m->hasTo('manager@test.com'));
        Mail::assertNotSent(OverdueFeeNotification::class, fn ($m) => $m->hasTo('authority@test.com'));
    }

    public function test_respects_configured_threshold(): void
    {
        $this->seedRoles();
        config(['alerts.days_threshold' => 10]);
        User::factory()->create()->assignRole('admin');

        $this->createOverdueFee(daysAgo: 15);

        $this->artisan('fees:send-alerts')->assertSuccessful();

        Mail::assertSent(OverdueFeeNotification::class, 1);
    }

    public function test_is_idempotent_within_same_day(): void
    {
        $this->seedRoles();
        User::factory()->create()->assignRole('admin');
        $this->createOverdueFee(daysAgo: 40);

        $this->artisan('fees:send-alerts')->assertSuccessful();
        $this->artisan('fees:send-alerts')->assertSuccessful();

        Mail::assertSent(OverdueFeeNotification::class, 1);
    }

    public function test_force_flag_bypasses_idempotency(): void
    {
        $this->seedRoles();
        User::factory()->create()->assignRole('admin');
        $this->createOverdueFee(daysAgo: 40);

        $this->artisan('fees:send-alerts')->assertSuccessful();
        $this->artisan('fees:send-alerts --force')->assertSuccessful();

        Mail::assertSent(OverdueFeeNotification::class, 2);
    }

    public function test_different_days_allow_resend(): void
    {
        $this->seedRoles();
        User::factory()->create()->assignRole('admin');
        $this->createOverdueFee(daysAgo: 40);

        Carbon::setTestNow(now());
        $this->artisan('fees:send-alerts')->assertSuccessful();

        Carbon::setTestNow(now()->addDay());
        Cache::flush();
        $this->artisan('fees:send-alerts')->assertSuccessful();

        Mail::assertSent(OverdueFeeNotification::class, 2);
    }

    private function createOverdueFee(int $daysAgo): Fee
    {
        $sport = Sport::factory()->create();
        $category = FeeCategory::factory()->create();
        $member = Member::factory()->create([
            'sport_id' => $sport->id,
            'fee_category_id' => $category->id,
            'status' => 'active',
        ]);
        $period = now()->subDays($daysAgo)->format('Y-m');

        return Fee::factory()->create([
            'member_id' => $member->id,
            'status' => 'overdue',
            'period' => $period,
        ]);
    }

    private function seedRoles(): void
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'authority', 'guard_name' => 'web']);
    }
}
