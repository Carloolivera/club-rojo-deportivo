<?php

namespace Tests\Feature\Fee;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class AlertScheduleTest extends TestCase
{
    public function test_overdue_alerts_scheduled_daily_at_9am(): void
    {
        $schedule = app(Schedule::class);
        $events = $schedule->events();

        $event = collect($events)->first(
            fn ($e) => str_contains($e->command ?? '', 'fees:send-alerts')
        );

        $this->assertNotNull($event, 'El schedule fees:send-alerts no está registrado');
        $this->assertEquals('0 9 * * *', $event->expression);
    }

    public function test_mark_overdue_scheduled_daily_at_midnight(): void
    {
        $schedule = app(Schedule::class);
        $events = $schedule->events();

        $event = collect($events)->first(
            fn ($e) => str_contains($e->command ?? '', 'fees:mark-overdue')
        );

        $this->assertNotNull($event, 'El schedule fees:mark-overdue no está registrado');
        $this->assertEquals('5 0 * * *', $event->expression);
    }
}
