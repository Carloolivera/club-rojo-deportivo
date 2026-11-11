<?php

namespace Tests\Feature\Fee;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MpWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makeSignature(string $dataId, string $requestId, int $ts, string $secret): string
    {
        $manifest  = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $hash      = hash_hmac('sha256', $manifest, $secret);
        return "ts={$ts},v1={$hash}";
    }

    private function createPendingFee(): Fee
    {
        $category = FeeCategory::factory()->create(['amount' => 3000]);
        $sport    = Sport::factory()->create();
        $member   = Member::factory()->create([
            'status' => 'active',
            'fee_category_id' => $category->id,
            'sport_id' => $sport->id,
        ]);
        return Fee::factory()->create([
            'member_id' => $member->id,
            'period'    => '2026-04',
            'amount'    => 3000,
            'status'    => 'pending',
        ]);
    }

    public function test_marks_fee_as_paid_on_valid_payment_webhook(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $fee = $this->createPendingFee();
        $paymentId = '99887766';
        $requestId = 'req-test-001';
        $ts = time();

        Http::fake([
            "https://api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'id'                 => $paymentId,
                'status'             => 'approved',
                'external_reference' => (string) $fee->id,
                'transaction_amount' => 3000,
                'date_approved'      => now()->toIso8601String(),
            ]),
        ]);

        $signature = $this->makeSignature($paymentId, $requestId, $ts, 'test-secret');

        $this->postJson(route('mp.webhook'), [
            'type'  => 'payment',
            'data'  => ['id' => $paymentId],
            'action' => 'payment.updated',
        ], [
            'x-signature'  => $signature,
            'x-request-id' => $requestId,
        ])->assertOk();

        $fee->refresh();
        $this->assertEquals('paid', $fee->status);
        $this->assertEquals('mp', $fee->payment_method);
        $this->assertEquals($paymentId, $fee->mp_payment_id);
        $this->assertNotNull($fee->paid_at);
    }

    public function test_rejects_webhook_with_invalid_signature(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $this->postJson(route('mp.webhook'), [
            'type' => 'payment',
            'data' => ['id' => '12345'],
        ], [
            'x-signature'  => 'ts=12345,v1=invalidsignature',
            'x-request-id' => 'req-bad',
        ])->assertStatus(401);
    }

    public function test_ignores_non_payment_events(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $paymentId = '11111111';
        $requestId = 'req-test-002';
        $ts = time();
        $signature = $this->makeSignature($paymentId, $requestId, $ts, 'test-secret');

        $this->postJson(route('mp.webhook'), [
            'type' => 'merchant_order',
            'data' => ['id' => $paymentId],
        ], [
            'x-signature'  => $signature,
            'x-request-id' => $requestId,
        ])->assertOk();
    }
}
