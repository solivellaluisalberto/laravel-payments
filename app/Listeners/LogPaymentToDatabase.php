<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Guarda el pago en la base de datos
 */
class LogPaymentToDatabase
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        try {
            // TODO: En producción, usar un modelo Payment con Eloquent
            // Por ahora, simulamos el guardado
            
            $paymentData = [
                'order_id' => $event->orderId,
                'payment_id' => $event->result->paymentId,
                'provider' => $event->provider->value,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'state' => $event->result->state->value,
                'customer_email' => $event->customerEmail,
                'metadata' => json_encode($event->metadata),
                'completed_at' => now(),
            ];

            // Simulación de guardado en DB
            Log::info('💾 Payment logged to database', $paymentData);

            // En producción, usarías algo como:
            /*
            Payment::create([
                'order_id' => $event->orderId,
                'payment_id' => $event->result->paymentId,
                'provider' => $event->provider,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'state' => $event->result->state,
                'customer_email' => $event->customerEmail,
                'metadata' => $event->metadata,
                'completed_at' => now(),
            ]);
            */

            // O actualizar una orden existente:
            /*
            Order::where('order_id', $event->orderId)->update([
                'payment_id' => $event->result->paymentId,
                'payment_status' => 'completed',
                'paid_at' => now(),
            ]);
            */

        } catch (\Exception $e) {
            Log::error('Failed to log payment to database', [
                'order_id' => $event->orderId,
                'error' => $e->getMessage(),
            ]);
            
            // Re-lanzar la excepción si es crítico que esto se guarde
            // throw $e;
        }
    }
}
