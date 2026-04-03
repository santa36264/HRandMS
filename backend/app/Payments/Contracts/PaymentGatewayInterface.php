<?php

namespace App\Payments\Contracts;

use App\Payments\DTOs\PaymentRequestDTO;
use App\Payments\DTOs\PaymentResponseDTO;
use App\Payments\DTOs\WebhookDTO;

interface PaymentGatewayInterface
{
    /**
     * Unique gateway identifier (e.g. 'telebirr', 'cbe_birr').
     */
    public function getName(): string;

    /**
     * Human-readable label shown in UI.
     */
    public function getLabel(): string;

    /**
     * Supported currency codes.
     */
    public function getSupportedCurrencies(): array;

    /**
     * Initiate a payment and return a response with redirect URL or token.
     */
    public function initiate(PaymentRequestDTO $request): PaymentResponseDTO;

    /**
     * Verify a transaction status by ID (polling / manual check).
     */
    public function verify(string $transactionId): PaymentResponseDTO;

    /**
     * Parse and validate an incoming webhook payload.
     * Throws PaymentWebhookException if signature is invalid.
     */
    public function handleWebhook(array $payload, array $headers): WebhookDTO;

    /**
     * Verify the webhook signature/HMAC.
     */
    public function verifyWebhookSignature(array $payload, array $headers): bool;
}
