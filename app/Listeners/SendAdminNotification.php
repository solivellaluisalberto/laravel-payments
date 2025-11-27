<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía notificación al administrador cuando se completa un pago
 */
class SendAdminNotification
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        try {
            $adminEmail = config('payments.admin_email', 'admin@example.com');

            $notificationData = [
                'admin_email' => $adminEmail,
                'event_type' => 'payment_completed',
                'order_id' => $event->orderId,
                'amount' => number_format($event->amount, 2),
                'currency' => $event->currency,
                'provider' => strtoupper($event->provider->value),
                'payment_id' => $event->result->paymentId,
                'customer_email' => $event->customerEmail ?? 'N/A',
            ];

            // Simulación de notificación
            Log::info('📢 Admin notification sent', $notificationData);

            // En producción, podrías:

            // 1. Enviar email al admin
            /*
            Mail::to($adminEmail)->send(
                new AdminPaymentNotification($event)
            );
            */

            // 2. Enviar notificación a Slack
            /*
            Notification::route('slack', config('services.slack.webhook_url'))
                ->notify(new PaymentReceivedNotification($event));
            */

            // 3. Enviar SMS/WhatsApp para pagos grandes
            /*
            if ($event->amount >= 1000) {
                // Twilio, WhatsApp Business API, etc.
            }
            */

            // 4. Dashboard en tiempo real (Broadcasting)
            /*
            broadcast(new PaymentReceivedBroadcast($event));
            */

        } catch (\Exception $e) {
            Log::error('Failed to send admin notification', [
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
        return true; // Ejecutar en background
    }
}
