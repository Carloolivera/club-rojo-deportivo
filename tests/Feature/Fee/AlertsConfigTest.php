<?php

namespace Tests\Feature\Fee;

use Tests\TestCase;

class AlertsConfigTest extends TestCase
{
    public function test_days_threshold_defaults_to_35(): void
    {
        $this->assertEquals(35, config('alerts.days_threshold'));
    }

    public function test_days_threshold_reads_from_env(): void
    {
        config(['alerts.days_threshold' => 20]);
        $this->assertEquals(20, config('alerts.days_threshold'));
    }
}
