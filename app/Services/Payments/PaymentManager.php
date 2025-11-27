<?php

namespace App\Services\Payments;

use App\Enums\PaymentProvider;

class PaymentManager
{
    private array $gateways = [];

    /**
     * Obtener el gateway para un proveedor específico
     */
    public function driver(PaymentProvider $provider): PaymentGateway
    {
        // Cachear la instancia del gateway
        if (isset($this->gateways[$provider->value])) {
            return $this->gateways[$provider->value];
        }

        $gateway = match ($provider) {
            PaymentProvider::STRIPE => $this->createStripeGateway(),
            PaymentProvider::REDSYS => $this->createRedsysGateway(),
            PaymentProvider::PAYPAL => $this->createPayPalGateway(),
            PaymentProvider::CASH => throw new \Exception('Cash payment does not require online processing'),
        };

        $this->gateways[$provider->value] = $gateway;

        return $gateway;
    }

    /**
     * Crear instancia de Stripe Gateway
     */
    private function createStripeGateway(): StripePaymentService
    {
        return new StripePaymentService(
            apiKey: config('payments.stripe.secret_key')
        );
    }

    /**
     * Crear instancia de Redsys Gateway
     */
    private function createRedsysGateway(): RedsysPaymentService
    {
        return new RedsysPaymentService(
            merchantCode: config('payments.redsys.merchant_code'),
            secretKey: config('payments.redsys.secret_key'),
            terminal: config('payments.redsys.terminal'),
            environment: config('payments.redsys.environment')
        );
    }

    /**
     * Crear instancia de PayPal Gateway
     */
    private function createPayPalGateway(): PayPalPaymentService
    {
        return new PayPalPaymentService(
            clientId: config('payments.paypal.client_id'),
            clientSecret: config('payments.paypal.client_secret'),
            environment: config('payments.paypal.environment')
        );
    }
}
