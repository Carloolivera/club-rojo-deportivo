<?php

namespace Tests\Feature\Member;

use App\Models\MemberPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPeriodIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_periodo_sin_fecha_fin_esta_activo(): void
    {
        $period = MemberPeriod::factory()->create(['ended_at' => null]);

        $this->assertTrue($period->isActive());
    }

    public function test_periodo_con_fecha_fin_no_esta_activo(): void
    {
        $period = MemberPeriod::factory()->closed()->create();

        $this->assertFalse($period->isActive());
    }

    public function test_isActive_refleja_estado_real_tras_guardar(): void
    {
        $period = MemberPeriod::factory()->create(['ended_at' => null]);
        $this->assertTrue($period->isActive());

        $period->update(['ended_at' => now()]);
        $this->assertFalse($period->fresh()->isActive());
    }
}
