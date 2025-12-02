<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Payments\PaymentGateway driver(\App\Enums\PaymentProvider|string $provider)
 * @method static void extend(string $name, callable $driver)
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'payment-manager';
    }
}

