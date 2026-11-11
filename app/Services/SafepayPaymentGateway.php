<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SafepayPaymentGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.safepay.secret_key'))
            && filled(config('services.safepay.api_key'))
            && filled(config('services.safepay.webhook_secret'));
    }

    public function assertReadyFor(string $currency): void
    {
        if (! in_array(strtoupper($currency), ['PKR', 'USD'], true)) {
            throw new RuntimeException('Safepay checkout is configured for PKR or USD only.');
        }
        if (! $this->isConfigured()) {
            throw new RuntimeException('Safepay sandbox credentials are not configured yet.');
        }
        if (config('services.safepay.environment') === 'production' && ! config('services.safepay.production_enabled')) {
            throw new RuntimeException('Production payments are disabled until the application owner explicitly enables them.');
        }
    }

    /** @return array{reference: string, checkout_url: string} */
    public function createHostedCheckout(int $amountMinor, string $currency, string $orderReference, string $returnUrl, string $cancelUrl, ?callable $trackerCreated = null): array
    {
        $this->assertReadyFor($currency);
        if ($amountMinor < 1) {
            throw new RuntimeException('Safepay requires a positive amount.');
        }

        $base = rtrim(config('services.safepay.api_host'), '/');
        $headers = ['X-SFPY-MERCHANT-SECRET' => config('services.safepay.secret_key')];
        $session = Http::acceptJson()->asJson()->withHeaders($headers)->timeout(12)->post($base.'/order/payments/v3/', [
            'merchant_api_key' => config('services.safepay.api_key'),
            'intent' => config('services.safepay.intent', 'CYBERSOURCE'),
            'mode' => 'payment',
            'entry_mode' => 'raw',
            'currency' => strtoupper($currency),
            'amount' => $amountMinor,
            'metadata' => ['order_id' => $orderReference],
            'include_fees' => false,
        ]);
        if (! $session->successful() || ! is_string($session->json('data.tracker.token'))) {
            $this->logProviderFailure('create_payment_tracker', $session);
            throw new RuntimeException('Safepay did not create a valid payment tracker. Check sandbox configuration and currency.');
        }
        $tracker = $session->json('data.tracker.token');
        if ($trackerCreated) $trackerCreated($tracker);

        $auth = Http::acceptJson()->asJson()->withHeaders($headers)->timeout(12)->post($base.'/client/passport/v1/token', []);
        if (! $auth->successful() || ! is_string($auth->json('data'))) {
            $this->logProviderFailure('create_checkout_auth_token', $auth);
            throw new RuntimeException('Safepay did not return a checkout authentication token.');
        }

        return [
            'reference' => $tracker,
            'checkout_url' => $this->checkoutUrl($tracker, $auth->json('data'), $returnUrl, $cancelUrl),
        ];
    }

    public function refreshHostedCheckout(string $tracker, string $currency, string $returnUrl, string $cancelUrl): string
    {
        $this->assertReadyFor($currency);
        if (! preg_match('/^track_[A-Za-z0-9-]+$/', $tracker)) {
            throw new RuntimeException('Safepay payment reference is not valid for checkout.');
        }

        $base = rtrim(config('services.safepay.api_host'), '/');
        $auth = Http::acceptJson()->asJson()
            ->withHeaders(['X-SFPY-MERCHANT-SECRET' => config('services.safepay.secret_key')])
            ->timeout(12)->post($base.'/client/passport/v1/token', []);

        if (! $auth->successful() || ! is_string($auth->json('data'))) {
            $this->logProviderFailure('refresh_checkout_auth_token', $auth);
            throw new RuntimeException('Safepay did not return a checkout authentication token.');
        }

        return $this->checkoutUrl($tracker, $auth->json('data'), $returnUrl, $cancelUrl);
    }

    private function checkoutUrl(string $tracker, string $authToken, string $returnUrl, string $cancelUrl): string
    {
        // The tracker + time-based token flow is Safepay's embedded checkout
        // protocol. `/checkout/pay` is a different/legacy flow and rejects the
        // session even when its `env` parameter is present.
        return rtrim(config('services.safepay.checkout_host'), '/').'/embedded?'.http_build_query([
            'tracker' => $tracker,
            'tbt' => $authToken,
            'environment' => config('services.safepay.environment', 'sandbox'),
            'source' => 'custom',
            'redirect_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function verifiesWebhook(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('services.safepay.webhook_secret');
        if ($secret === '' || ! is_string($signature) || ! preg_match('/^[a-fA-F0-9]{128}$/', $signature)) {
            return false;
        }
        return hash_equals(hash_hmac('sha512', $rawBody, $secret), strtolower($signature));
    }

    private function logProviderFailure(string $stage, \Illuminate\Http\Client\Response $response): void
    {
        $errors = $response->json('status.errors', []);
        $errorCodes = collect(is_array($errors) ? $errors : [])
            ->map(function ($error): ?string {
                if (is_string($error)) {
                    return preg_match('/^[A-Z0-9_.-]{1,60}$/i', $error) ? $error : 'provider_error';
                }
                if (is_array($error)) {
                    foreach (['code', 'type'] as $key) {
                        if (isset($error[$key]) && is_string($error[$key])) {
                            return Str::limit(preg_replace('/[^A-Z0-9_.-]/i', '', $error[$key]) ?? '', 60, '');
                        }
                    }
                }
                return null;
            })
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();

        $message = $response->json('status.message');
        Log::warning('Safepay checkout API request failed.', [
            'stage' => $stage,
            'http_status' => $response->status(),
            'provider_message' => is_string($message) ? Str::limit($message, 120, '') : null,
            'provider_error_codes' => $errorCodes,
            'tracker_token_returned' => is_string($response->json('data.tracker.token')),
            'auth_token_returned' => is_string($response->json('data')),
        ]);
    }
}
