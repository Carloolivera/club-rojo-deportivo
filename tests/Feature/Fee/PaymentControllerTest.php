<?php

namespace Tests\Feature\Fee;

use App\Http\Controllers\PaymentController;
use App\Models\Fee;
use App\Models\Member;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function signedUrl(Fee $fee): string
    {
        return URL::signedRoute('member.fees.pay', ['fee' => $fee->id]);
    }

    public function test_rejects_unsigned_request(): void
    {
        $fee = Fee::factory()->create(['status' => 'pending']);

        $this->get(route('member.fees.pay', $fee))
            ->assertStatus(403);
    }

    public function test_rejects_already_paid_fee(): void
    {
        $fee = Fee::factory()->create(['status' => 'paid']);

        $this->get($this->signedUrl($fee))
            ->assertStatus(422);
    }

    public function test_redirects_to_existing_preference_without_calling_mp(): void
    {
        $fee = Fee::factory()->create([
            'status'           => 'pending',
            'mp_preference_id' => 'existing-pref-123',
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldNotReceive('createPreference');
        });

        $this->get($this->signedUrl($fee))
            ->assertRedirect();
    }

    public function test_creates_preference_and_redirects_when_none_exists(): void
    {
        $fee = Fee::factory()->create(['status' => 'overdue', 'mp_preference_id' => null]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($fee) {
            $mock->shouldReceive('createPreference')
                ->once()
                ->with(\Mockery::on(fn($f) => $f->id === $fee->id))
                ->andReturn(['init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=new-pref']);
        });

        $this->get($this->signedUrl($fee))
            ->assertRedirect('https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=new-pref');
    }
}
