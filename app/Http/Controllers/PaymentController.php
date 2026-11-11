<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private MercadoPagoService $mp) {}

    public function redirect(Request $request, Fee $fee)
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_if(in_array($fee->status, ['paid']), 422, 'La cuota ya fue pagada.');

        if ($fee->mp_preference_id) {
            $initPoint = app()->isProduction()
                ? "https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id={$fee->mp_preference_id}"
                : "https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id={$fee->mp_preference_id}";

            return redirect($initPoint);
        }

        $result = $this->mp->createPreference($fee);

        return redirect($result['init_point']);
    }
}
