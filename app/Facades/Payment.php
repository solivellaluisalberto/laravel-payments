<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Payments\PaymentGateway driver(\App\Enums\PaymentProvider $provider)
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'payment-manager';
    }
}

