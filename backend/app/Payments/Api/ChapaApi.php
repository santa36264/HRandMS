<?php

namespace App\Payments\Api;

use App\Payments\Exceptions\PaymentInitiationException;
use App\Payments\Exceptions\PaymentVerificationException;
use App\Payments\Exceptions\PaymentWebhookException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChapaApi
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('payments.chapa.secret_key');
        $this->baseUrl   = rtrim(config('payments.chapa.base_url', 'https://api.chapa.co/v1'), '/');
    }

    public function initiatePayment(array $params): array
    {
        $this->validateInitiateParams($params);

        // Chapa customization.title: max 16 chars, only letters/numbers/hyphens/underscores/spaces/dots
        $title = substr(preg_replace('/[^a-zA-Z0-9\-_ .]/', '', $params['title'] ?? config('app.name')), 0, 16);
        $desc  = substr(preg_replace('/[^a-zA-Z0-9\-_ .]/', '', $params['description'] ?? 'Hotel Booking'), 0, 255);

        $payload = [
            'amount'        => number_format((float) $params['amount'], 2, '.', ''),
            'currency'      => $params['currency'] ?? 'ETB',
            'email'         => $params['email'],
            'first_name'    => $params['first_name'],
            'last_name'     => $params['last_name'] ?? '',
            'tx_ref'        => $params['tx_ref'],
            'callback_url'  => $params['callback_url'],
            'return_url'    => $params['return_url'],
            'customization' => [
                'title'       => $title,
                'description' => $desc,
            ],
        ];

        // Only include phone_number if it looks like a valid Ethiopian number (09/07XXXXXXXX)
        $phone = trim($params['phone'] ?? '');
        if (preg_match('/^0[79]\d{8}$/', $phone)) {
            $payload['phone_number'] = $phone;
        }

        Log::info('[Chapa] Initiating payment', [
            'tx_ref'    => $params['tx_ref'],
            'amount'    => $params['amount'],
            'has_phone' => isset($payload['phone_number']),
            'title'     => $title,
        ]);

        $response = Http::withToken($this->secretKey)
            ->timeout(30)
            ->post("{$this->baseUrl}/transaction/initialize", $payload);

        $body = $response->json() ?? [];

        Log::info('[Chapa] Initiate response', [
            'status'  => $body['status']  ?? null,
            'message' => $body['message'] ?? null,
        ]);

        if (! $response->successful() || ($body['status'] ?? '') !== 'success') {
            $raw = $body['message'] ?? "HTTP {$response->status()}";
            $msg = is_array($raw)
                ? implode(', ', array_map(fn($v) => is_array($v) ? implode(', ', $v) : (string) $v, $raw))
                : (string) $raw;
            throw new PaymentInitiationException("[Chapa] Initiation failed: {$msg}");
        }

        return [
            'success'      => true,
            'tx_ref'       => $params['tx_ref'],
            'checkout_url' => $body['data']['checkout_url'] ?? null,
            'raw'          => $body,
        ];
    }

    public function verifyPayment(string $txRef): array
    {
        Log::info('[Chapa] Verifying payment', ['tx_ref' => $txRef]);

        $response = Http::withToken($this->secretKey)
            ->timeout(15)
            ->get("{$this->baseUrl}/transaction/verify/{$txRef}");

        $body = $response->json() ?? [];

        Log::info('[Chapa] Verify response', ['status' => $body['status'] ?? null]);

        if (! $response->successful() || ($body['status'] ?? '') !== 'success') {
            $raw = $body['message'] ?? "HTTP {$response->status()}";
            $msg = is_array($raw)
                ? implode(', ', array_map(fn($v) => is_array($v) ? implode(', ', $v) : (string) $v, $raw))
                : (string) $raw;
            throw new PaymentVerificationException("[Chapa] Verification failed: {$msg}");
        }

        $data        = $body['data'] ?? [];
        $chapaStatus = strtolower($data['status'] ?? '');

        return [
            'success'   => $chapaStatus === 'success',
            'status'    => $this->normalizeStatus($chapaStatus),
            'tx_ref'    => $data['tx_ref']    ?? $txRef,
            'amount'    => (float) ($data['amount'] ?? 0),
            'currency'  => $data['currency']  ?? 'ETB',
            'chapa_ref' => $data['chapa_ref'] ?? '',
            'paid_at'   => $data['created_at'] ?? null,
            'raw'       => $body,
        ];
    }

    public function handleWebhook(array $payload, array $headers, string $rawBody = ''): array
    {
        Log::info('[Chapa] Webhook received', ['tx_ref' => $payload['tx_ref'] ?? null]);

        if (! $this->verifyWebhookSignature($rawBody, $headers)) {
            Log::warning('[Chapa] Webhook signature mismatch');
            throw new PaymentWebhookException('[Chapa] Invalid webhook signature.');
        }

        foreach (['tx_ref', 'status'] as $field) {
            if (empty($payload[$field])) {
                throw new PaymentWebhookException("[Chapa] Missing required webhook field: {$field}");
            }
        }

        $chapaStatus = strtolower($payload['status'] ?? '');

        return [
            'tx_ref'    => $payload['tx_ref'],
            'status'    => $this->normalizeStatus($chapaStatus),
            'amount'    => (float) ($payload['amount'] ?? 0),
            'currency'  => $payload['currency'] ?? 'ETB',
            'chapa_ref' => $payload['chapa_ref'] ?? '',
            'raw'       => $payload,
        ];
    }

    public function verifyWebhookSignature(string $rawBody, array $headers): bool
    {
        $webhookSecret = config('payments.chapa.webhook_secret', '');

        if (empty($webhookSecret)) {
            Log::warning('[Chapa] No webhook secret configured — skipping signature check.');
            return true;
        }

        $received = $headers['x-chapa-signature'][0]
            ?? $headers['x-chapa-signature']
            ?? $headers['X-Chapa-Signature']
            ?? '';

        if (empty($received)) return false;

        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        return hash_equals($expected, $received);
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'success'               => 'completed',
            'failed', 'abandoned'   => 'failed',
            'refunded', 'reversed'  => 'refunded',
            default                 => 'pending',
        };
    }

    private function validateInitiateParams(array $params): void
    {
        $required = ['amount', 'email', 'first_name', 'tx_ref', 'callback_url', 'return_url'];
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new PaymentInitiationException("[Chapa] Missing required field: {$field}");
            }
        }
        if ((float) $params['amount'] <= 0) {
            throw new PaymentInitiationException('[Chapa] Amount must be greater than zero.');
        }
    }
}
