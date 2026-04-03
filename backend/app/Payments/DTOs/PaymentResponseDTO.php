<?php

namespace App\Payments\DTOs;

class PaymentResponseDTO
{
    public function __construct(
        public readonly bool   $success,
        public readonly string $transactionId,
        public readonly string $status,           // pending | completed | failed
        public readonly float  $amount,
        public readonly string $currency,
        public readonly string $gateway,
        public readonly ?string $paymentUrl,      // redirect URL for hosted pages
        public readonly ?string $checkoutToken,   // short-lived token if needed
        public readonly array  $rawResponse = [],
        public readonly ?string $errorMessage = null,
    ) {}

    public static function success(
        string $transactionId,
        float  $amount,
        string $currency,
        string $gateway,
        ?string $paymentUrl    = null,
        ?string $checkoutToken = null,
        array  $rawResponse    = [],
    ): self {
        return new self(
            success:        true,
            transactionId:  $transactionId,
            status:         'pending',
            amount:         $amount,
            currency:       $currency,
            gateway:        $gateway,
            paymentUrl:     $paymentUrl,
            checkoutToken:  $checkoutToken,
            rawResponse:    $rawResponse,
        );
    }

    public static function failure(string $gateway, string $message, array $raw = []): self
    {
        return new self(
            success:       false,
            transactionId: '',
            status:        'failed',
            amount:        0,
            currency:      'ETB',
            gateway:       $gateway,
            paymentUrl:    null,
            checkoutToken: null,
            rawResponse:   $raw,
            errorMessage:  $message,
        );
    }

    public function toArray(): array
    {
        return [
            'success'        => $this->success,
            'transaction_id' => $this->transactionId,
            'status'         => $this->status,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'gateway'        => $this->gateway,
            'payment_url'    => $this->paymentUrl,
            'checkout_token' => $this->checkoutToken,
            'error_message'  => $this->errorMessage,
        ];
    }
}
