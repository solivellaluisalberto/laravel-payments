<?php

namespace App\Enums;

enum PaymentState: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::COMPLETED => 'Completado',
            self::FAILED => 'Fallido',
            self::REFUNDED => 'Reembolsado',
            self::CANCELLED => 'Cancelado',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::REFUNDED => 'orange',
            self::CANCELLED => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::REFUNDED,
            self::CANCELLED,
        ]);
    }
}

