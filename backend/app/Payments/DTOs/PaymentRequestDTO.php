<?php

namespace App\Payments\DTOs;

class PaymentRequestDTO
{
    public function __construct(
        public readonly int    $bookingId,
        public readonly int    $userId,
        public readonly float  $amount,
        public readonly string $currency,
        public readonly string $customerPhone,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $description,
        public readonly string $callbackUrl,
        public readonly string $returnUrl,
        public readonly array  $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            bookingId:     $data['booking_id'],
            userId:        $data['user_id'],
            amount:        (float) $data['amount'],
            currency:      $data['currency']       ?? 'ETB',
            customerPhone: $data['customer_phone'],
            customerName:  $data['customer_name'],
            customerEmail: $data['customer_email'],
            description:   $data['description']    ?? 'Hotel Booking Payment',
            callbackUrl:   $data['callback_url'],
            returnUrl:     $data['return_url'],
            metadata:      $data['metadata']        ?? [],
        );
    }
}
