<?php

namespace App\DTOs;

use App\Enums\PaymentType;

class PaymentResponse
{
    public function __construct(
        public readonly PaymentType $type,
        public readonly array $data,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $clientSecret = null,
        public readonly ?string $formHtml = null,
    ) {}

    public function isRedirect(): bool
    {
        return $this->type === PaymentType::REDIRECT;
    }

    public function isApi(): bool
    {
        return $this->type === PaymentType::API;
    }

    public function isAlternative(): bool
    {
        return $this->type === PaymentType::ALTERNATIVE;
    }
}
