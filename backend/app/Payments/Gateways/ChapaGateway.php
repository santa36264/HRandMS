<?php

namespace App\Payments\Gateways;

use App\Payments\Api\ChapaApi;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentRequestDTO;
use App\Payments\DTOs\PaymentResponseDTO;
use App\Payments\DTOs\WebhookDTO;
use Illuminate\Http\Request;

class ChapaGateway implements PaymentGatewayInterface
{
    public function __construct(private ChapaApi $api) {}

    public function getName(): string  { return 'chapa'; }
    public function getLabel(): string { return 'Chapa'; }
    public function getSupportedCurrencies(): array { return ['ETB']; }

    public function initiate(PaymentRequestDTO $request): PaymentResponseDTO
    {
        // Split name into first/last for Chapa
        $nameParts = explode(' ', trim($request->customerName), 2);
        $firstName = $nameParts[0];
        $lastName  = $nameParts[1] ?? '';

        $txRef = 'HRMS-' . $request->bookingId . '-' . time();

        // Sanitize phone: Chapa requires Ethiopian format 09XXXXXXXX or 07XXXXXXXX
        // Strip spaces, dashes, +251 prefix. If result doesn't look Ethiopian, omit it.
        $phone = preg_replace('/[\s\-\(\)]/', '', $request->customerPhone ?? '');
        if (str_starts_with($phone, '+251')) {
            $phone = '0' . substr($phone, 4);
        } elseif (str_starts_with($phone, '251')) {
            $phone = '0' . substr($phone, 3);
        }
        // Only pass if it looks like a valid Ethiopian mobile number
        if (!preg_match('/^0[79]\d{8}$/', $phone)) {
            $phone = '';
        }

        $result = $this->api->initiatePayment([
            'amount'       => $request->amount,
            'currency'     => 'ETB',
            'email'        => $request->customerEmail,
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'phone'        => $phone,
            'tx_ref'       => $txRef,
            'callback_url' => $request->callbackUrl,
            'return_url'   => $request->returnUrl,
            'title'        => config('app.name'),
            'description'  => $request->description,
        ]);

        return PaymentResponseDTO::success(
            transactionId:  $txRef,
            amount:         $request->amount,
            currency:       'ETB',
            gateway:        $this->getName(),
            paymentUrl:     $result['checkout_url'],
            checkoutToken:  null,
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
        // Pass raw body for signature verification
        $rawBody = request()->getContent();
        $result  = $this->api->handleWebhook($payload, $headers, $rawBody);

        return new WebhookDTO(
            transactionId: $result['tx_ref'],
            status:        $result['status'],
            amount:        $result['amount'],
            currency:      'ETB',
            gateway:       $this->getName(),
            rawPayload:    $result['raw'],
        );
    }

    public function verifyWebhookSignature(array $payload, array $headers): bool
    {
        $rawBody = request()->getContent();
        return $this->api->verifyWebhookSignature($rawBody, $headers);
    }
}
