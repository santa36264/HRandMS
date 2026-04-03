<?php

namespace App\Payments\Api;

use App\Payments\Exceptions\PaymentInitiationException;
use App\Payments\Exceptions\PaymentVerificationException;
use App\Payments\Exceptions\PaymentWebhookException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CBE Birr API Integration
 *
 * Handles payment initiation, verification, callback processing,
 * and refunds for the CBE Birr mobile payment gateway
 * (Commercial Bank of Ethiopia).
 *
 * Auth: HMAC-SHA256 request signing via X-Api-Key + X-Signature headers.
 */
class CbeBirrApi
{
    private string $merchantId;
    private string $apiKey;
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('payments.cbe_birr.merchant_id');
        $this->apiKey     = config('payments.cbe_birr.api_key');
        $this->secretKey  = config('payments.cbe_birr.secret_key');
        $this->baseUrl    = rtrim(config('payments.cbe_birr.base_url'), '/');
    }

    // =========================================================================
    // PUBLIC METHODS
    // =========================================================================

    /**
     * Initiate a CBE Birr payment.
     *
     * @param  array{
     *   order_id: string,
     *   amount: float,
     *   description: string,
     *   customer_phone: string,
     *   customer_name: string,
     *   customer_email: string,
     *   callback_url: string,
     *   return_url: string,
     *   metadata?: array,
     *   expiry_minutes?: int,
     * } $params
     *
     * @return array{
     *   success: bool,
     *   order_id: string,
     *   payment_url: string|null,
     *   checkout_token: string|null,
     *   expires_at: string|null,
     *   raw: array,
     * }
     *
     * @throws PaymentInitiationException
     */
    public function initiatePayment(array $params): array
    {
        $this->validateInitiateParams($params);

        $timestamp = now()->toIso8601String();
        $nonce     = $this->generateNonce();

        $payload = [
            'merchantId'     => $this->merchantId,
            'orderId'        => $params['order_id'],
            'amount'         => number_format((float) $params['amount'], 2, '.', ''),
            'currency'       => 'ETB',
            'description'    => $params['description'],
            'customerPhone'  => $this->normalizePhone($params['customer_phone']),
            'customerName'   => $params['customer_name'],
            'customerEmail'  => $params['customer_email'],
            'callbackUrl'    => $params['callback_url'],
            'returnUrl'      => $params['return_url'],
            'expiryMinutes'  => $params['expiry_minutes'] ?? 30,
            'metadata'       => $params['metadata'] ?? [],
            'timestamp'      => $timestamp,
            'nonce'          => $nonce,
        ];

        $signature = $this->buildSignature($payload);

        Log::info('[CBE Birr] Initiating payment', [
            'order_id' => $params['order_id'],
            'amount'   => $params['amount'],
        ]);

        $response = Http::withHeaders($this->buildHeaders($signature))
            ->timeout(30)
            ->post("{$this->baseUrl}/api/v1/payment/initiate", $payload);

        $body = $response->json() ?? [];

        Log::info('[CBE Birr] Initiate response', ['body' => $body]);

        if (! $response->successful() || ! ($body['success'] ?? false)) {
            $msg = $body['message'] ?? "HTTP {$response->status()}";
            throw new PaymentInitiationException("[CBE Birr] Initiation failed: {$msg}");
        }

        return [
            'success'        => true,
            'order_id'       => $params['order_id'],
            'payment_url'    => $body['data']['paymentUrl']    ?? null,
            'checkout_token' => $body['data']['checkoutToken'] ?? null,
            'expires_at'     => $body['data']['expiresAt']     ?? null,
            'raw'            => $body,
        ];
    }

    /**
     * Verify a payment by its order ID.
     *
     * @return array{
     *   success: bool,
     *   status: string,          // completed|pending|failed|refunded
     *   order_id: string,
     *   transaction_id: string,  // CBE internal reference
     *   amount: float,
     *   cbe_status: string,      // raw CBE status string
     *   paid_at: string|null,
     *   raw: array,
     * }
     *
     * @throws PaymentVerificationException
     */
    public function verifyPayment(string $orderId): array
    {
        Log::info('[CBE Birr] Verifying payment', ['order_id' => $orderId]);

        $timestamp = now()->toIso8601String();
        $nonce     = $this->generateNonce();

        $queryParams = [
            'merchantId' => $this->merchantId,
            'orderId'    => $orderId,
            'timestamp'  => $timestamp,
            'nonce'      => $nonce,
        ];

        $signature = $this->buildSignature($queryParams);

        $response = Http::withHeaders($this->buildHeaders($signature))
            ->timeout(15)
            ->get("{$this->baseUrl}/api/v1/payment/status/{$orderId}", $queryParams);

        $body = $response->json() ?? [];

        Log::info('[CBE Birr] Verify response', ['body' => $body]);

        if (! $response->successful() || ! ($body['success'] ?? false)) {
            $msg = $body['message'] ?? "HTTP {$response->status()}";
            throw new PaymentVerificationException("[CBE Birr] Verification failed: {$msg}");
        }

        $data      = $body['data'] ?? [];
        $cbeStatus = $data['status'] ?? '';

        return [
            'success'        => $this->normalizeStatus($cbeStatus) === 'completed',
            'status'         => $this->normalizeStatus($cbeStatus),
            'order_id'       => $data['orderId']        ?? $orderId,
            'transaction_id' => $data['transactionId']  ?? '',
            'amount'         => (float) ($data['amount'] ?? 0),
            'cbe_status'     => $cbeStatus,
            'paid_at'        => $data['paidAt']         ?? null,
            'raw'            => $body,
        ];
    }

    /**
     * Parse and validate an inbound CBE Birr callback/webhook.
     *
     * @param  array $payload  Raw POST body from CBE Birr
     * @param  array $headers  Request headers (for signature verification)
     *
     * @return array{
     *   order_id: string,
     *   transaction_id: string,
     *   status: string,
     *   amount: float,
     *   cbe_status: string,
     *   paid_at: string|null,
     *   raw: array,
     * }
     *
     * @throws PaymentWebhookException
     */
    public function handleCallback(array $payload, array $headers): array
    {
        Log::info('[CBE Birr] Callback received', ['payload' => $payload]);

        // 1. Verify HMAC signature
        if (! $this->verifyCallbackSignature($payload, $headers)) {
            Log::warning('[CBE Birr] Callback signature mismatch', ['payload' => $payload]);
            throw new PaymentWebhookException('[CBE Birr] Invalid callback signature.');
        }

        // 2. Validate required fields
        $required = ['orderId', 'transactionId', 'status', 'amount'];
        foreach ($required as $field) {
            if (! isset($payload[$field])) {
                throw new PaymentWebhookException("[CBE Birr] Missing required callback field: {$field}");
            }
        }

        $cbeStatus = $payload['status'];

        Log::info('[CBE Birr] Callback validated', [
            'order_id'       => $payload['orderId'],
            'transaction_id' => $payload['transactionId'],
            'status'         => $cbeStatus,
        ]);

        return [
            'order_id'       => $payload['orderId'],
            'transaction_id' => $payload['transactionId'],
            'status'         => $this->normalizeStatus($cbeStatus),
            'amount'         => (float) $payload['amount'],
            'cbe_status'     => $cbeStatus,
            'paid_at'        => $payload['paidAt'] ?? null,
            'raw'            => $payload,
        ];
    }

    /**
     * Refund a completed CBE Birr payment.
     *
     * @throws PaymentInitiationException
     */
    public function refund(string $orderId, float $amount, string $reason = ''): array
    {
        $refundId  = 'REF-' . $orderId . '-' . time();
        $timestamp = now()->toIso8601String();
        $nonce     = $this->generateNonce();

        $payload = [
            'merchantId'   => $this->merchantId,
            'orderId'      => $orderId,
            'refundId'     => $refundId,
            'refundAmount' => number_format($amount, 2, '.', ''),
            'currency'     => 'ETB',
            'reason'       => $reason ?: 'Customer requested refund',
            'timestamp'    => $timestamp,
            'nonce'        => $nonce,
        ];

        $signature = $this->buildSignature($payload);

        $response = Http::withHeaders($this->buildHeaders($signature))
            ->timeout(30)
            ->post("{$this->baseUrl}/api/v1/payment/refund", $payload);

        $body = $response->json() ?? [];

        Log::info('[CBE Birr] Refund response', ['body' => $body, 'order_id' => $orderId]);

        if (! $response->successful() || ! ($body['success'] ?? false)) {
            throw new PaymentInitiationException('[CBE Birr] Refund failed: ' . ($body['message'] ?? ''));
        }

        return [
            'success'   => true,
            'refund_id' => $refundId,
            'order_id'  => $orderId,
            'amount'    => $amount,
            'raw'       => $body,
        ];
    }

    /**
     * Check merchant account balance (admin use).
     *
     * @throws PaymentVerificationException
     */
    public function getBalance(): array
    {
        $timestamp = now()->toIso8601String();
        $nonce     = $this->generateNonce();

        $params    = ['merchantId' => $this->merchantId, 'timestamp' => $timestamp, 'nonce' => $nonce];
        $signature = $this->buildSignature($params);

        $response = Http::withHeaders($this->buildHeaders($signature))
            ->timeout(10)
            ->get("{$this->baseUrl}/api/v1/merchant/balance", $params);

        $body = $response->json() ?? [];

        if (! $response->successful() || ! ($body['success'] ?? false)) {
            throw new PaymentVerificationException('[CBE Birr] Balance check failed: ' . ($body['message'] ?? ''));
        }

        return [
            'balance'  => (float) ($body['data']['balance']  ?? 0),
            'currency' => $body['data']['currency'] ?? 'ETB',
            'raw'      => $body,
        ];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Build HMAC-SHA256 signature.
     * Payload is JSON-encoded after sorting keys, then signed with secret key.
     */
    private function buildSignature(array $payload): string
    {
        $data = $payload;
        unset($data['signature'], $data['sign']);

        ksort($data);

        return hash_hmac('sha256', json_encode($data, JSON_UNESCAPED_UNICODE), $this->secretKey);
    }

    /**
     * Verify the HMAC signature on an inbound callback.
     * CBE Birr sends signature in X-Cbe-Signature header as "sha256=<hash>".
     */
    private function verifyCallbackSignature(array $payload, array $headers): bool
    {
        // Normalize header key (can arrive in different cases)
        $received = $headers['x-cbe-signature']
            ?? $headers['X-Cbe-Signature']
            ?? $headers['x-cbe-signature'][0]
            ?? '';

        if (empty($received)) {
            Log::warning('[CBE Birr] No signature header found in callback.');
            return false;
        }

        // Strip "sha256=" prefix if present
        $received = str_replace('sha256=', '', $received);

        $data = $payload;
        unset($data['signature'], $data['sign']);
        ksort($data);

        $expected = hash_hmac('sha256', json_encode($data, JSON_UNESCAPED_UNICODE), $this->secretKey);

        return hash_equals($expected, $received);
    }

    /**
     * Build standard request headers for every CBE Birr API call.
     */
    private function buildHeaders(string $signature): array
    {
        return [
            'X-Api-Key'     => $this->apiKey,
            'X-Merchant-Id' => $this->merchantId,
            'X-Signature'   => $signature,
            'X-Timestamp'   => now()->toIso8601String(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Normalize Ethiopian phone numbers to +251 format.
     * Accepts: 09xxxxxxxx, 9xxxxxxxx, +2519xxxxxxxx, 2519xxxxxxxx
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '251')) {
            return '+' . $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+251' . substr($phone, 1);
        }

        if (strlen($phone) === 9) {
            return '+251' . $phone;
        }

        return '+' . $phone;
    }

    /**
     * Map CBE Birr status strings to normalized statuses.
     */
    private function normalizeStatus(string $status): string
    {
        return match (strtolower($status)) {
            'success', 'completed', 'paid' => 'completed',
            'failed', 'cancelled', 'expired', 'rejected' => 'failed',
            'refunded', 'reversed' => 'refunded',
            default => 'pending',
        };
    }

    /**
     * Generate a cryptographically random nonce.
     */
    private function generateNonce(int $length = 16): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validate required fields for initiatePayment.
     */
    private function validateInitiateParams(array $params): void
    {
        $required = [
            'order_id', 'amount', 'description',
            'customer_phone', 'customer_name', 'customer_email',
            'callback_url', 'return_url',
        ];

        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new PaymentInitiationException("[CBE Birr] Missing required field: {$field}");
            }
        }

        if ((float) $params['amount'] <= 0) {
            throw new PaymentInitiationException('[CBE Birr] Amount must be greater than zero.');
        }
    }
}
