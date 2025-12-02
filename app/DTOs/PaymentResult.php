<?php

namespace App\DTOs;

use App\Exceptions\PaymentValidationException;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $paymentId = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $message = null,
        public readonly array $data = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        // Validar que status no esté vacío
        if (empty(trim($this->status))) {
            throw PaymentValidationException::validationFailed(
                'status',
                'Status cannot be empty'
            );
        }

        // Validar longitud de status
        if (strlen($this->status) > 50) {
            throw PaymentValidationException::invalidFieldLength(
                'status',
                strlen($this->status),
                50
            );
        }

        // Validar que si success = true, debe haber paymentId o transactionId
        if ($this->success && $this->paymentId === null && $this->transactionId === null) {
            throw PaymentValidationException::validationFailed(
                'paymentId/transactionId',
                'Successful payment must have either paymentId or transactionId'
            );
        }

        // Validar estados conocidos
        $validStatuses = [
            'pending', 'processing', 'completed', 'failed', 'cancelled',
            'refunded', 'partial_refund', 'disputed', 'expired',
            'authorized', 'requires_action', 'requires_payment_method',
            'requires_confirmation', 'requires_capture', 'error',
            'not_supported', 'unavailable'
        ];

        $normalizedStatus = strtolower(str_replace(['-', '_'], '', $this->status));
        $normalizedValidStatuses = array_map(
            fn($s) => strtolower(str_replace(['-', '_'], '', $s)),
            $validStatuses
        );

        if (! in_array($normalizedStatus, $normalizedValidStatuses)) {
            // Solo warning, no exception, ya que los proveedores pueden tener estados custom
            // Pero validamos que no sea un string extraño
            if (! preg_match('/^[a-z0-9_\-]+$/i', $this->status)) {
                throw PaymentValidationException::validationFailed(
                    'status',
                    'Status contains invalid characters'
                );
            }
        }

        // Validar longitud de IDs si están presentes
        if ($this->paymentId !== null && strlen($this->paymentId) > 255) {
            throw PaymentValidationException::invalidFieldLength(
                'paymentId',
                strlen($this->paymentId),
                255
            );
        }

        if ($this->transactionId !== null && strlen($this->transactionId) > 255) {
            throw PaymentValidationException::invalidFieldLength(
                'transactionId',
                strlen($this->transactionId),
                255
            );
        }

        // Validar longitud de message si está presente
        if ($this->message !== null && strlen($this->message) > 1000) {
            throw PaymentValidationException::invalidFieldLength(
                'message',
                strlen($this->message),
                1000
            );
        }
    }
}
