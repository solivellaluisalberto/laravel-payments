<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CARD = 'card';
    case BIZUM = 'bizum';
    case CASH = 'cash';

    /**
     * Obtener el código de método de pago para Redsys
     * Según documentación: DS_MERCHANT_PAYMETHODS
     */
    public function getRedsysCode(): string
    {
        return match ($this) {
            self::CARD => 'T',    // T = Solo tarjeta (no iupay)
            self::BIZUM => 'z',   // z = Bizum
            self::CASH => 'C',    // C = Todos los métodos disponibles
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Tarjeta',
            self::BIZUM => 'Bizum',
            self::CASH => 'Efectivo',
        };
    }
}
