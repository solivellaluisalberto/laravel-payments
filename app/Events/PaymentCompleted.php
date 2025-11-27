<?php

namespace App\Events;

use App\DTOs\PaymentResult;
use App\Enums\PaymentProvider;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando un pago se completa exitosamente
 *
 * Este evento es agnóstico del proveedor de pago, permitiendo
 * ejecutar acciones comunes sin importar si fue Stripe, Redsys o PayPal
 */
class PaymentCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Información del pago completado
     */
    public function __construct(
        public readonly PaymentProvider $provider,
        public readonly PaymentResult $result,
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly array $metadata = [],
        public readonly ?string $customerEmail = null
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payments'),
        ];
    }

    /**
     * Obtener datos del pago para logging/notificaciones
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_id' => $this->result->paymentId,
            'status' => $this->result->status,
            'customer_email' => $this->customerEmail,
            'metadata' => $this->metadata,
            'completed_at' => now()->toISOString(),
        ];
    }
}
