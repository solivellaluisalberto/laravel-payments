<?php

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Exceptions\PaymentConfigurationException;

class PaymentManager
{
    private array $gateways = [];

    private array $customDrivers = [];

    /**
     * Registrar un driver personalizado
     *
     * @param string $name Nombre del driver (ej: 'mercadopago', 'square')
     * @param callable $driver Closure que retorna una instancia de PaymentGateway
     */
    public function extend(string $name, callable $driver): void
    {
        $this->customDrivers[$name] = $driver;
    }

    /**
     * Obtener el gateway para un proveedor específico
     *
     * @param PaymentProvider|string $provider Proveedor de pago (enum o string)
     * @return PaymentGateway
     */
    public function driver(PaymentProvider|string $provider): PaymentGateway
    {
        // Normalizar a string
        $providerName = $provider instanceof PaymentProvider ? $provider->value : $provider;

        // Cachear la instancia del gateway
        if (isset($this->gateways[$providerName])) {
            return $this->gateways[$providerName];
        }

        // Primero verificar drivers personalizados
        if (isset($this->customDrivers[$providerName])) {
            $gateway = $this->customDrivers[$providerName]($this);
            
            if (! $gateway instanceof PaymentGateway) {
                throw new \InvalidArgumentException(
                    "Custom driver '{$providerName}' must return an instance of PaymentGateway"
                );
            }

            $this->gateways[$providerName] = $gateway;

            return $gateway;
        }

        // Luego los drivers del paquete (requiere enum)
        if (! $provider instanceof PaymentProvider) {
            try {
                $provider = PaymentProvider::from($providerName);
            } catch (\ValueError $e) {
                throw PaymentConfigurationException::unsupportedProvider($providerName);
            }
        }

        $gateway = match ($provider) {
            PaymentProvider::STRIPE => $this->createStripeGateway(),
            PaymentProvider::REDSYS => $this->createRedsysGateway(),
            PaymentProvider::PAYPAL => $this->createPayPalGateway(),
            PaymentProvider::CASH => throw new \Exception('Cash payment does not require online processing'),
        };

        $this->gateways[$providerName] = $gateway;

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
