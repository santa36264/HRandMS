<?php

namespace App\Payments\DTOs;

class WebhookDTO
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $status,       // completed | failed | refunded
        public readonly float  $amount,
        public readonly string $currency,
        public readonly string $gateway,
        public readonly array  $rawPayload,
    ) {}
}
