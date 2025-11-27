<?php

return [

    /*
    |--------------------------------------------------------------------------
    | General Payment Configuration
    |--------------------------------------------------------------------------
    */

    // Email del administrador para notificaciones de pagos
    'admin_email' => env('PAYMENT_ADMIN_EMAIL', 'admin@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para Stripe payment gateway
    | Obtén tus claves en: https://dashboard.stripe.com/apikeys
    |
    */

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redsys Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para Redsys TPV (bancos españoles)
    | Solicita tus credenciales a tu banco
    |
    */

    'redsys' => [
        'merchant_code' => env('REDSYS_MERCHANT_CODE'),
        'secret_key' => env('REDSYS_SECRET_KEY'),
        'terminal' => env('REDSYS_TERMINAL', '1'),
        'environment' => env('REDSYS_ENVIRONMENT', 'test'), // test o live
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para PayPal
    | Obtén tus credenciales en: https://developer.paypal.com/dashboard/
    |
    */

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'), // sandbox o live
    ],

];
