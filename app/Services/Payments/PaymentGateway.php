<?php

namespace App\Services\Payments;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\PaymentResult;

interface PaymentGateway
{
    /**
     * Iniciar un pago
     */
    public function initiate(PaymentRequest $request): PaymentResponse;

    /**
     * Capturar/confirmar un pago
     */
    public function capture(string $paymentId): PaymentResult;

    /**
     * Reembolsar un pago
     */
    public function refund(string $paymentId, ?float $amount = null): PaymentResult;

    /**
     * Obtener el estado de un pago
     */
    public function getStatus(string $paymentId): PaymentResult;

    /**
     * Verificar callback de retorno del proveedor
     * Solo para proveedores con flujo de redirección (Redsys, PayPal)
     */
    public function verifyCallback(array $postData): PaymentResult;
}
