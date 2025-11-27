<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case STRIPE = 'stripe';
    case REDSYS = 'redsys';
    case PAYPAL = 'paypal';
    case CASH = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::STRIPE => 'Stripe',
            self::REDSYS => 'Redsys',
            self::PAYPAL => 'PayPal',
            self::CASH => 'Efectivo',
        };
    }
}
