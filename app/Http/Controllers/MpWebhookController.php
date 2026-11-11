<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpWebhookController extends Controller
{
    public function __construct(private MercadoPagoService $mp) {}

    public function __invoke(Request $request): JsonResponse
    {
        $xSignature = $request->header('x-signature', '');
        $xRequestId = $request->header('x-request-id', '');
        $dataId     = $request->input('data.id', '');

        if (! $this->mp->validateWebhookSignature($xSignature, $dataId, $xRequestId)) {
            Log::warning('MP webhook: firma inválida', [
                'x_signature' => $xSignature,
                'data_id'     => $dataId,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if ($request->input('type') !== 'payment') {
            return response()->json(['ok' => true]);
        }

        $paymentId = $dataId;

        $response = Http::withToken(config('mercadopago.access_token'))
            ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

        if (! $response->ok()) {
            Log::error('MP webhook: error consultando pago', ['payment_id' => $paymentId]);
            return response()->json(['error' => 'Payment fetch failed'], 200);
        }

        $payment = $response->json();

        if ($payment['status'] !== 'approved') {
            return response()->json(['ok' => true]);
        }

        $feeId = $payment['external_reference'] ?? null;
        if (! $feeId) {
            return response()->json(['ok' => true]);
        }

        $fee = Fee::find($feeId);
        if (! $fee || $fee->status === 'paid') {
            return response()->json(['ok' => true]);
        }

        $fee->update([
            'status'         => 'paid',
            'payment_method' => 'mp',
            'mp_payment_id'  => $paymentId,
            'paid_at'        => now(),
        ]);

        activity()
            ->performedOn($fee)
            ->withProperties(['payment_id' => $paymentId, 'amount' => $payment['transaction_amount']])
            ->log('paid_via_mp');

        return response()->json(['ok' => true]);
    }
}
