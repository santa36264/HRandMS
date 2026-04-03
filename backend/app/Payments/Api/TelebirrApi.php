<?php

namespace App\Payments\Api;

use App\Payments\Exceptions\PaymentInitiationException;
use App\Payments\Exceptions\PaymentVerificationException;
use App\Payments\Exceptions\PaymentWebhookException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telebirr API Integration
 *
 * Handles payment initiation, verification, and callback processing
 * for the Telebirr mobile payment gateway (Ethio Telecom).
 *
 * Docs: https://developer.ethiotelecom.et/docs/telebirr
 */
class TelebirrApi
{
    private const TOKEN_CACHE_KEY = 'telebirr_access_token';
    private const TOKEN_TTL       = 3500; // seconds (token valid 1hr, refresh at 58min)

    private string $appId;
    private string $appKey;
    private string $publicKey;
    private string $shortCode;
    private string $baseUrl;
    private string $tokenUrl;

    public function __construct()
    {
        $this->appId     = config('payments.telebirr.app_id');
        $this->appKey    = config('payments.telebirr.app_key');
        $this->publicKey = config('payments.telebirr.public_key');
        $this->shortCode = config('payments.telebirr.short_code');
        $this->baseUrl   = rtrim(config('payments.telebirr.base_url'), '/');
        $this->tokenUrl  = config('payments.telebirr.token_url', $this->baseUrl . '/payment/v1/token');
    }

    // =========================================================================
    // PUBLIC METHODS
    // =========================================================================

    /**
     * Initiate a Telebirr payment.
     *
     * @param  array{
     *   out_trade_no: string,
     *   subject: string,
     *   total_amount: float,
     *   notify_url: string,
     *   return_url: string,
     *   timeout_express?: string,
     *   receive_name?: string,
     * } $params
     *
     * @return array{
     *   success: bool,
     *   to_pay_url: string|null,
     *   prepay_id: string|null,
     *   out_trade_no: string,
     *   raw: array,
     * }
     *
     * @throws PaymentInitiationException
     */
    public function initiatePayment(array $params): array
    {
        $this->validateInitiateParams($params);

        $timestamp = now()->format('YmdHis');
        $nonce     = $this->generateNonce();

        $ussdPayload = [
            'appid'          => $this->appId,
            'outTradeNo'     => $params['out_trade_no'],
            'subject'        => $params['subject'],
            'totalAmount'    => number_format((float) $params['total_amount'], 2, '.', ''),
            'shortCode'      => $this->shortCode,
            'timeoutExpress' => $params['timeout_express'] ?? '30',
            'notifyUrl'      => $params['notify_url'],
            'returnUrl'      => $params['return_url'],
            'receiveName'    => $params['receive_name'] ?? config('app.name'),
            'nonce'          => $nonce,
            'timestamp'      => $timestamp,
        ];

        $sign            = $this->buildSignature($ussdPayload);
        $ussdPayload['sign'] = $sign;

        $encryptedUssd = $this->encryptWithPublicKey(json_encode($ussdPayload));

        Log::info('[Telebirr] Initiating payment', [
            'out_trade_no' => $params['out_trade_no'],
            'amount'       => $params['total_amount'],
        ]);

        $response = Http::withToken($this->getAccessToken())
            ->timeout(30)
            ->post("{$this->baseUrl}/payment/v1/merchant/preOrder", [
                'appid' => $this->appId,
                'sign'  => $sign,
                'ussd'  => $encryptedUssd,
            ]);

        $body = $response->json() ?? [];

        Log::info('[Telebirr] Initiate response', ['body' => $body]);

        if (! $response->successful() || ($body['code'] ?? '') !== '0') {
            $msg = $body['msg'] ?? "HTTP {$response->status()}";
            throw new PaymentInitiationException("[Telebirr] Initiation failed: {$msg}");
        }

        return [
            'success'      => true,
            'to_pay_url'   => $body['data']['toPayUrl']  ?? null,
            'prepay_id'    => $body['data']['prepayId']  ?? null,
            'out_trade_no' => $params['out_trade_no'],
            'raw'          => $body,
        ];
    }

    /**
     * Verify a payment by its out_trade_no (order ID).
     *
     * @return array{
     *   success: bool,
     *   status: string,       // completed|pending|failed
     *   trade_no: string,     // Telebirr internal trade number
     *   out_trade_no: string,
     *   amount: float,
     *   trade_state: string,  // raw Telebirr state
     *   paid_at: string|null,
     *   raw: array,
     * }
     *
     * @throws PaymentVerificationException
     */
    public function verifyPayment(string $outTradeNo): array
    {
        Log::info('[Telebirr] Verifying payment', ['out_trade_no' => $outTradeNo]);

        $sign = $this->buildSignature(['outTradeNo' => $outTradeNo]);

        $response = Http::withToken($this->getAccessToken())
            ->timeout(15)
            ->post("{$this->baseUrl}/payment/v1/merchant/queryPayment", [
                'appid'      => $this->appId,
                'outTradeNo' => $outTradeNo,
                'sign'       => $sign,
            ]);

        $body = $response->json() ?? [];

        Log::info('[Telebirr] Verify response', ['body' => $body]);

        if (! $response->successful() || ($body['code'] ?? '') !== '0') {
            $msg = $body['msg'] ?? "HTTP {$response->status()}";
            throw new PaymentVerificationException("[Telebirr] Verification failed: {$msg}");
        }

        $data       = $body['data'] ?? [];
        $tradeState = $data['tradeState'] ?? '';

        return [
            'success'      => $tradeState === 'SUCCESS',
            'status'       => $this->normalizeStatus($tradeState),
            'trade_no'     => $data['tradeNo']     ?? '',
            'out_trade_no' => $data['outTradeNo']  ?? $outTradeNo,
            'amount'       => (float) ($data['totalAmount'] ?? 0),
            'trade_state'  => $tradeState,
            'paid_at'      => $data['transactionTime'] ?? null,
            'raw'          => $body,
        ];
    }

    /**
     * Parse and validate an inbound Telebirr callback/webhook.
     *
     * @param  array $payload  Raw POST body from Telebirr
     * @return array{
     *   out_trade_no: string,
     *   trade_no: string,
     *   status: string,
     *   amount: float,
     *   trade_state: string,
     *   paid_at: string|null,
     *   raw: array,
     * }
     *
     * @throws PaymentWebhookException
     */
    public function handleCallback(array $payload): array
    {
        Log::info('[Telebirr] Callback received', ['payload' => $payload]);

        // 1. Verify signature
        if (! $this->verifyCallbackSignature($payload)) {
            Log::warning('[Telebirr] Callback signature mismatch', ['payload' => $payload]);
            throw new PaymentWebhookException('[Telebirr] Invalid callback signature.');
        }

        // 2. Validate required fields
        $required = ['outTradeNo', 'tradeNo', 'tradeState', 'totalAmount'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new PaymentWebhookException("[Telebirr] Missing required callback field: {$field}");
            }
        }

        $tradeState = $payload['tradeState'];

        Log::info('[Telebirr] Callback validated', [
            'out_trade_no' => $payload['outTradeNo'],
            'state'        => $tradeState,
        ]);

        return [
            'out_trade_no' => $payload['outTradeNo'],
            'trade_no'     => $payload['tradeNo'],
            'status'       => $this->normalizeStatus($tradeState),
            'amount'       => (float) $payload['totalAmount'],
            'trade_state'  => $tradeState,
            'paid_at'      => $payload['transactionTime'] ?? null,
            'raw'          => $payload,
        ];
    }

    /**
     * Refund a completed payment.
     *
     * @throws PaymentInitiationException
     */
    public function refund(string $outTradeNo, float $amount, string $reason = ''): array
    {
        $refundNo = 'REF-' . $outTradeNo . '-' . time();

        $payload = [
            'appid'        => $this->appId,
            'outTradeNo'   => $outTradeNo,
            'outRefundNo'  => $refundNo,
            'refundAmount' => number_format($amount, 2, '.', ''),
            'refundReason' => $reason ?: 'Customer requested refund',
            'nonce'        => $this->generateNonce(),
            'timestamp'    => now()->format('YmdHis'),
        ];

        $payload['sign'] = $this->buildSignature($payload);

        $response = Http::withToken($this->getAccessToken())
            ->timeout(30)
            ->post("{$this->baseUrl}/payment/v1/merchant/refund", $payload);

        $body = $response->json() ?? [];

        Log::info('[Telebirr] Refund response', ['body' => $body, 'out_trade_no' => $outTradeNo]);

        if (! $response->successful() || ($body['code'] ?? '') !== '0') {
            throw new PaymentInitiationException('[Telebirr] Refund failed: ' . ($body['msg'] ?? ''));
        }

        return [
            'success'      => true,
            'refund_no'    => $refundNo,
            'out_trade_no' => $outTradeNo,
            'amount'       => $amount,
            'raw'          => $body,
        ];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Fetch or return cached OAuth access token.
     */
    private function getAccessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_TTL, function () {
            $response = Http::timeout(10)
                ->asForm()
                ->post($this->tokenUrl, [
                    'appid'      => $this->appId,
                    'grant_type' => 'client_credentials',
                    'sign'       => $this->buildSignature(['appid' => $this->appId]),
                ]);

            $body = $response->json() ?? [];

            if (! $response->successful() || empty($body['access_token'])) {
                throw new PaymentInitiationException('[Telebirr] Failed to obtain access token.');
            }

            Log::info('[Telebirr] Access token refreshed.');

            return $body['access_token'];
        });
    }

    /**
     * Build MD5 signature: sort params → query string → append key → MD5 → uppercase.
     */
    private function buildSignature(array $params): string
    {
        // Remove sign field if present
        unset($params['sign']);

        // Sort by key ascending
        ksort($params);

        // Build query string without URL encoding
        $str = urldecode(http_build_query($params));

        // Append secret key
        $str .= '&key=' . $this->appKey;

        return strtoupper(md5($str));
    }

    /**
     * Verify the signature on an inbound callback.
     */
    private function verifyCallbackSignature(array $payload): bool
    {
        $received = $payload['sign'] ?? '';
        if (empty($received)) return false;

        $expected = $this->buildSignature($payload);

        return hash_equals($expected, strtoupper($received));
    }

    /**
     * RSA-encrypt the USSD payload with Telebirr's public key.
     */
    private function encryptWithPublicKey(string $data): string
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split($this->publicKey, 64, "\n")
            . "-----END PUBLIC KEY-----";

        $key = openssl_pkey_get_public($pem);

        if (! $key) {
            throw new PaymentInitiationException('[Telebirr] Invalid public key configuration.');
        }

        // RSA supports max 245 bytes per chunk with PKCS1 padding (2048-bit key)
        $chunkSize = 245;
        $encrypted = '';

        foreach (str_split($data, $chunkSize) as $chunk) {
            if (! openssl_public_encrypt($chunk, $encryptedChunk, $key, OPENSSL_PKCS1_PADDING)) {
                throw new PaymentInitiationException('[Telebirr] Encryption failed.');
            }
            $encrypted .= $encryptedChunk;
        }

        return base64_encode($encrypted);
    }

    /**
     * Map Telebirr trade states to normalized statuses.
     */
    private function normalizeStatus(string $tradeState): string
    {
        return match (strtoupper($tradeState)) {
            'SUCCESS'              => 'completed',
            'CLOSED', 'PAYERROR'  => 'failed',
            'REFUND'               => 'refunded',
            default                => 'pending',
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
        $required = ['out_trade_no', 'subject', 'total_amount', 'notify_url', 'return_url'];

        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new PaymentInitiationException("[Telebirr] Missing required field: {$field}");
            }
        }

        if ((float) $params['total_amount'] <= 0) {
            throw new PaymentInitiationException('[Telebirr] Amount must be greater than zero.');
        }
    }
}
