<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Exceptions\UnsupportedGatewayException;
use App\Payments\Gateways\ChapaGateway;

class PaymentManager
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function __construct()
    {
        // Gateways are registered via PaymentServiceProvider
    }

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->getName()] = $gateway;
    }

    public function gateway(string $name): PaymentGatewayInterface
    {
        if (! isset($this->gateways[$name])) {
            throw new UnsupportedGatewayException("Payment gateway [{$name}] is not supported.");
        }
        return $this->gateways[$name];
    }

    /** Returns all registered gateways for UI listing */
    public function available(): array
    {
        return array_values(array_map(fn($g) => [
            'name'       => $g->getName(),
            'label'      => $g->getLabel(),
            'currencies' => $g->getSupportedCurrencies(),
        ], $this->gateways));
    }
}
