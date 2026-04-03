<?php

namespace App\Payments\Gateways;

use App\Payments\Api\CbeBirrApi;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentRequestDTO;
use App\Payments\DTOs\PaymentResponseDTO;
use App\Payments\DTOs\WebhookDTO;

/**
 * CBE Birr gateway adapter — delegates to CbeBirrApi.
 */
class CbeBirrGateway implements PaymentGatewayInterface
{
    public function __construct(private CbeBirrApi $api) {}

    public function getName(): string  { return 'cbe_birr'; }
    public function getLabel(): string { return 'CBE Birr'; }
    public function getSupportedCurrencies(): array { return ['ETB']; }

    public function initiate(PaymentRequestDTO $request): PaymentResponseDTO
    {
        $result = $this->api->initiatePayment([
            'order_id'       => 'HRMS-CBE-' . $request->bookingId . '-' . time(),
            'amount'         => $request->amount,
            'description'    => $request->description,
            'customer_phone' => $request->customerPhone,
            'customer_name'  => $request->customerName,
            'customer_email' => $request->customerEmail,
            'callback_url'   => $request->callbackUrl,
            'return_url'     => $request->returnUrl,
            'metadata'       => $request->metadata,
        ]);

        return PaymentResponseDTO::success(
            transactionId:  $result['order_id'],
            amount:         $request->amount,
            currency:       'ETB',
            gateway:        $this->getName(),
            paymentUrl:     $result['payment_url'],
            checkoutToken:  $result['checkout_token'],
            rawResponse:    $result['raw'],
        );
    }

    public function verify(string $transactionId): PaymentResponseDTO
    {
        $result = $this->api->verifyPayment($transactionId);

        return new PaymentResponseDTO(
            success:       $result['success'],
            transactionId: $transactionId,
            status:        $result['status'],
            amount:        $result['amount'],
            currency:      'ETB',
            gateway:       $this->getName(),
            paymentUrl:    null,
            checkoutToken: null,
            rawResponse:   $result['raw'],
        );
    }

    public function handleWebhook(array $payload, array $headers): WebhookDTO
    {
        $result = $this->api->handleCallback($payload, $headers);

        return new WebhookDTO(
            transactionId: $result['order_id'],
            status:        $result['status'],
            amount:        $result['amount'],
            currency:      'ETB',
            gateway:       $this->getName(),
            rawPayload:    $result['raw'],
        );
    }

    public function verifyWebhookSignature(array $payload, array $headers): bool
    {
        // Handled inside CbeBirrApi::handleCallback
        return true;
    }
}
