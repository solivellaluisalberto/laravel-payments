<?php

namespace App\Providers;

use App\Events\PaymentCompleted;
use App\Listeners\LogPaymentToDatabase;
use App\Listeners\SendAdminNotification;
use App\Listeners\SendPaymentConfirmationEmail;
use App\Listeners\UpdateInventory;
use App\Services\Payments\PaymentManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('payment-manager', function ($app) {
            return new PaymentManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar listeners para el evento PaymentCompleted
        Event::listen(
            PaymentCompleted::class,
            [
                LogPaymentToDatabase::class,        // 1. Guardar en BD (crítico)
                SendPaymentConfirmationEmail::class, // 2. Email al cliente
                SendAdminNotification::class,        // 3. Notificar al admin
                UpdateInventory::class,              // 4. Actualizar inventario
            ]
        );
    }
}
