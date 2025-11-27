<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía email de confirmación al cliente cuando el pago se completa
 */
class SendPaymentConfirmationEmail
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        // Si no hay email del cliente, no podemos enviar nada
        if (!$event->customerEmail) {
            Log::info('Payment completed but no customer email provided', [
                'order_id' => $event->orderId,
            ]);
            return;
        }

        try {
            // TODO: En producción, implementar con Mail::send() y una vista
            // Por ahora, solo simulamos el envío
            
            $emailData = [
                'customer_email' => $event->customerEmail,
                'order_id' => $event->orderId,
                'amount' => number_format($event->amount, 2),
                'currency' => $event->currency,
                'payment_id' => $event->result->paymentId,
                'provider' => $event->provider->value,
            ];

            // Simulación de envío de email
            Log::info('📧 Payment confirmation email sent', $emailData);

            // En producción, usarías algo como:
            /*
            Mail::to($event->customerEmail)->send(
                new PaymentConfirmationMail($event)
            );
            */

        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email', [
                'order_id' => $event->orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determinar si el listener debe ser ejecutado en cola
     */
    public function shouldQueue(): bool
    {
        return true; // Ejecutar en background para no bloquear la respuesta
    }
}
