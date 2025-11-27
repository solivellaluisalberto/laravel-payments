<?php

namespace App\DTOs;

use App\Enums\PaymentMethod;

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
    ) {}
}

