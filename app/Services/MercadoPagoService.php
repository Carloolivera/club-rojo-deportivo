<?php

namespace App\Services;

use App\Models\Fee;
use Illuminate\Support\Facades\Http;

class MercadoPagoService
{
    /**
     * Create a MercadoPago checkout preference for the given fee.
     *
     * The $urls parameter allows callers (and tests) to inject notification/back URLs
     * without depending on named routes that may not yet be registered.
     *
     * @param  array{notification_url?: string, success_url?: string, failure_url?: string, pending_url?: string}  $urls
     */
    public function createPreference(Fee $fee, array $urls = []): array
    {
        $notificationUrl = $urls['notification_url']
            ?? (app()->routesAreCached() || $this->routeExists('mp.webhook')
                ? route('mp.webhook')
                : url('/mp/webhook'));

        $successUrl = $urls['success_url']
            ?? (app()->routesAreCached() || $this->routeExists('member.fees.payment')
                ? route('member.fees.payment', ['fee' => $fee->id, 'status' => 'success'])
                : url("/member/fees/{$fee->id}/payment?status=success"));

        $failureUrl = $urls['failure_url']
            ?? (app()->routesAreCached() || $this->routeExists('member.fees.payment')
                ? route('member.fees.payment', ['fee' => $fee->id, 'status' => 'failure'])
                : url("/member/fees/{$fee->id}/payment?status=failure"));

        $pendingUrl = $urls['pending_url']
            ?? (app()->routesAreCached() || $this->routeExists('member.fees.payment')
                ? route('member.fees.payment', ['fee' => $fee->id, 'status' => 'pending'])
                : url("/member/fees/{$fee->id}/payment?status=pending"));

        $response = Http::withToken(config('mercadopago.access_token'))
            ->post('https://api.mercadopago.com/checkout/preferences', [
                'items' => [[
                    'title'       => "Cuota {$fee->period} — Club Rojo Deportivo",
                    'quantity'    => 1,
                    'unit_price'  => (float) $fee->amount,
                    'currency_id' => 'ARS',
                ]],
                'external_reference' => (string) $fee->id,
                'notification_url'   => $notificationUrl,
                'back_urls' => [
                    'success' => $successUrl,
                    'failure' => $failureUrl,
                    'pending' => $pendingUrl,
                ],
                'auto_return' => 'approved',
            ]);

        $response->throw();

        $data = $response->json();

        $fee->update(['mp_preference_id' => $data['id']]);

        return [
            'preference_id' => $data['id'],
            'init_point'    => app()->isProduction() ? $data['init_point'] : $data['sandbox_init_point'],
        ];
    }

    /**
     * Validate the HMAC-SHA256 signature sent by MercadoPago in the X-Signature header.
     *
     * The manifest format is: "id:{dataId};request-id:{xRequestId};ts:{ts};"
     */
    public function validateWebhookSignature(string $xSignature, string $dataId, string $xRequestId): bool
    {
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) !== 2) {
                return false;
            }
            [$key, $value] = $pair;
            $parts[trim($key)] = trim($value);
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        if (abs(time() - (int) $parts['ts']) > 300) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$parts['ts']};";
        $expected = hash_hmac('sha256', $manifest, config('mercadopago.webhook_secret'));

        return hash_equals($expected, $parts['v1']);
    }

    /**
     * Check if a named route exists without throwing an exception.
     */
    private function routeExists(string $name): bool
    {
        return app('router')->has($name);
    }
}
