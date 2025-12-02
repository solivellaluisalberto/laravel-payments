<?php

namespace App\DTOs;

use App\Enums\PaymentMethod;
use App\Exceptions\PaymentValidationException;

class PaymentRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $orderId,
        public readonly array $metadata = [],
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
        public readonly ?string $notificationUrl = null,
        public readonly ?PaymentMethod $paymentMethod = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        // Validar monto
        if ($this->amount <= 0) {
            throw PaymentValidationException::invalidAmount(
                $this->amount,
                'Amount must be greater than 0'
            );
        }

        if ($this->amount > 999999.99) {
            throw PaymentValidationException::invalidAmount(
                $this->amount,
                'Amount exceeds maximum allowed (999999.99)'
            );
        }

        // Validar moneda (ISO 4217 = 3 caracteres)
        if (strlen($this->currency) !== 3) {
            throw PaymentValidationException::invalidCurrency($this->currency);
        }

        if (! ctype_alpha($this->currency)) {
            throw PaymentValidationException::invalidCurrency($this->currency);
        }

        // Validar Order ID
        if (empty(trim($this->orderId))) {
            throw PaymentValidationException::invalidOrderId(
                $this->orderId,
                'Order ID cannot be empty'
            );
        }

        if (strlen($this->orderId) > 255) {
            throw PaymentValidationException::invalidFieldLength(
                'orderId',
                strlen($this->orderId),
                255
            );
        }

        // Validar URLs si están presentes
        if ($this->returnUrl !== null && ! filter_var($this->returnUrl, FILTER_VALIDATE_URL)) {
            throw PaymentValidationException::invalidReturnUrl($this->returnUrl);
        }

        if ($this->cancelUrl !== null && ! filter_var($this->cancelUrl, FILTER_VALIDATE_URL)) {
            throw PaymentValidationException::validationFailed(
                'cancelUrl',
                'Must be a valid URL'
            );
        }

        if ($this->notificationUrl !== null && ! filter_var($this->notificationUrl, FILTER_VALIDATE_URL)) {
            throw PaymentValidationException::validationFailed(
                'notificationUrl',
                'Must be a valid URL'
            );
        }

        // Validar metadata
        if (isset($this->metadata['description']) && strlen($this->metadata['description']) > 500) {
            throw PaymentValidationException::invalidFieldLength(
                'description',
                strlen($this->metadata['description']),
                500
            );
        }

        // Validar email si está en metadata
        if (isset($this->metadata['customer_email'])) {
            if (! filter_var($this->metadata['customer_email'], FILTER_VALIDATE_EMAIL)) {
                throw PaymentValidationException::invalidEmail($this->metadata['customer_email']);
            }
        }
    }
}
