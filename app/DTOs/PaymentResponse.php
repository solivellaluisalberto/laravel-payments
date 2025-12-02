<?php

namespace App\DTOs;

use App\Enums\PaymentType;
use App\Exceptions\PaymentValidationException;

class PaymentResponse
{
    public function __construct(
        public readonly PaymentType $type,
        public readonly array $data,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $clientSecret = null,
        public readonly ?string $formHtml = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        // Validar que el tipo de respuesta tenga los datos correctos
        switch ($this->type) {
            case PaymentType::REDIRECT:
                if ($this->redirectUrl === null && $this->formHtml === null) {
                    throw PaymentValidationException::validationFailed(
                        'redirectUrl/formHtml',
                        'REDIRECT type requires either redirectUrl or formHtml'
                    );
                }

                if ($this->redirectUrl !== null && ! filter_var($this->redirectUrl, FILTER_VALIDATE_URL)) {
                    throw PaymentValidationException::validationFailed(
                        'redirectUrl',
                        'Must be a valid URL'
                    );
                }
                break;

            case PaymentType::API:
                if ($this->clientSecret === null) {
                    throw PaymentValidationException::missingRequiredField('clientSecret');
                }

                if (empty(trim($this->clientSecret))) {
                    throw PaymentValidationException::validationFailed(
                        'clientSecret',
                        'Cannot be empty'
                    );
                }
                break;

            case PaymentType::ALTERNATIVE:
                // Los pagos alternativos pueden tener estructura variable
                break;
        }

        // Validar que data no esté vacío
        if (empty($this->data)) {
            throw PaymentValidationException::validationFailed(
                'data',
                'Response data cannot be empty'
            );
        }
    }

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
