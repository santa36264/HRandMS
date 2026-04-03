<?php

namespace App\Payments\Gateways;

use App\Payments\Api\TelebirrApi;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentRequestDTO;
use App\Payments\DTOs\PaymentResponseDTO;
use App\Payments\DTOs\WebhookDTO;

/**
 * Telebirr gateway adapter — delegates to TelebirrApi.
 */
class TelebirrGateway implements PaymentGatewayInterface
{
    public function __construct(private TelebirrApi $api) {}

    public function getName(): string  { return 'telebirr'; }
    public function getLabel(): string { return 'Telebirr'; }
    public function getSupportedCurrencies(): array { return ['ETB']; }

    public function initiate(PaymentRequestDTO $request): PaymentResponseDTO
    {
        $result = $this->api->initiatePayment([
            'out_trade_no'    => 'HRMS-' . $request->bookingId . '-' . time(),
            'subject'         => $request->description,
            'total_amount'    => $request->amount,
            'notify_url'      => $request->callbackUrl,
            'return_url'      => $request->returnUrl,
            'receive_name'    => config('app.name'),
        ]);

        return PaymentResponseDTO::success(
            transactionId:  $result['out_trade_no'],
            amount:         $request->amount,
            currency:       'ETB',
            gateway:        $this->getName(),
            paymentUrl:     $result['to_pay_url'],
            checkoutToken:  $result['prepay_id'],
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
        $result = $this->api->handleCallback($payload);

        return new WebhookDTO(
            transactionId: $result['out_trade_no'],
            status:        $result['status'],
            amount:        $result['amount'],
            currency:      'ETB',
            gateway:       $this->getName(),
            rawPayload:    $result['raw'],
        );
    }

    public function verifyWebhookSignature(array $payload, array $headers): bool
    {
        // Signature verification is handled inside TelebirrApi::handleCallback
        return true;
    }
}
