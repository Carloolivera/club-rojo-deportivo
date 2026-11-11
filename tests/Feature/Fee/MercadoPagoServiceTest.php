<?php

namespace Tests\Feature\Fee;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_preference_and_returns_init_point(): void
    {
        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'id'                 => 'test-preference-id-123',
                'init_point'         => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=test-preference-id-123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=test-preference-id-123',
            ], 201),
        ]);

        $sport    = Sport::factory()->create();
        $category = FeeCategory::factory()->create(['amount' => 5000]);
        $member   = Member::factory()->create([
            'sport_id'        => $sport->id,
            'fee_category_id' => $category->id,
        ]);
        $fee = Fee::factory()->create([
            'member_id' => $member->id,
            'period'    => '2026-04',
            'amount'    => 5000,
            'status'    => 'pending',
        ]);

        $service = new MercadoPagoService();

        // Pass explicit URLs to avoid depending on named routes not yet registered.
        $result = $service->createPreference($fee, [
            'notification_url' => 'https://example.com/mp/webhook',
            'success_url'      => "https://example.com/member/fees/{$fee->id}/payment?status=success",
            'failure_url'      => "https://example.com/member/fees/{$fee->id}/payment?status=failure",
            'pending_url'      => "https://example.com/member/fees/{$fee->id}/payment?status=pending",
        ]);

        $this->assertArrayHasKey('preference_id', $result);
        $this->assertArrayHasKey('init_point', $result);
        $this->assertEquals('test-preference-id-123', $result['preference_id']);
        $this->assertEquals(
            'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=test-preference-id-123',
            $result['init_point']
        );
    }

    public function test_preference_persists_mp_preference_id_on_fee(): void
    {
        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'id'                 => 'pref-persisted-456',
                'init_point'         => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-persisted-456',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-persisted-456',
            ], 201),
        ]);

        $fee = Fee::factory()->pending()->create(['amount' => 3000, 'period' => '2026-03']);

        $service = new MercadoPagoService();
        $service->createPreference($fee, [
            'notification_url' => 'https://example.com/mp/webhook',
            'success_url'      => 'https://example.com/success',
            'failure_url'      => 'https://example.com/failure',
            'pending_url'      => 'https://example.com/pending',
        ]);

        $this->assertEquals('pref-persisted-456', $fee->fresh()->mp_preference_id);
    }

    public function test_validates_webhook_signature_correctly(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $ts        = time();
        $paymentId = '12345678';
        $requestId = 'req-abc-123';
        $manifest  = "id:{$paymentId};request-id:{$requestId};ts:{$ts};";
        $signature = hash_hmac('sha256', $manifest, 'test-secret');

        $service = new MercadoPagoService();
        $valid   = $service->validateWebhookSignature(
            "ts={$ts},v1={$signature}",
            $paymentId,
            $requestId
        );

        $this->assertTrue($valid);
    }

    public function test_rejects_invalid_webhook_signature(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $service = new MercadoPagoService();
        $valid   = $service->validateWebhookSignature(
            'ts=12345,v1=invalidsignature',
            '12345678',
            'req-abc-123'
        );

        $this->assertFalse($valid);
    }

    public function test_rejects_malformed_webhook_signature_header(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $service = new MercadoPagoService();

        $this->assertFalse($service->validateWebhookSignature('', '12345678', 'req-abc'));
        $this->assertFalse($service->validateWebhookSignature('garbage', '12345678', 'req-abc'));
    }

    public function test_rejects_replayed_webhook_signature(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $oldTs     = time() - 301; // más de 5 minutos atrás
        $paymentId = '12345678';
        $requestId = 'req-abc-123';
        $manifest  = "id:{$paymentId};request-id:{$requestId};ts:{$oldTs};";
        $signature = hash_hmac('sha256', $manifest, 'test-secret');

        $service = new \App\Services\MercadoPagoService();
        $valid   = $service->validateWebhookSignature(
            "ts={$oldTs},v1={$signature}",
            $paymentId,
            $requestId
        );

        $this->assertFalse($valid);
    }
}
