<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use Illuminate\Support\Facades\Log;

/**
 * Actualiza el inventario cuando se completa un pago
 */
class UpdateInventory
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        try {
            // Obtener items de la orden desde metadata
            $items = $event->metadata['items'] ?? [];

            if (empty($items)) {
                Log::info('No items to update inventory', [
                    'order_id' => $event->orderId,
                ]);
                return;
            }

            foreach ($items as $item) {
                $productId = $item['product_id'] ?? null;
                $quantity = $item['quantity'] ?? 1;

                if (!$productId) {
                    continue;
                }

                // Simulación de actualización de inventario
                Log::info('📦 Inventory updated', [
                    'order_id' => $event->orderId,
                    'product_id' => $productId,
                    'quantity_reduced' => $quantity,
                ]);

                // En producción, usarías algo como:
                /*
                $product = Product::find($productId);
                
                if ($product) {
                    $product->decrement('stock', $quantity);
                    
                    // Si el stock es bajo, notificar
                    if ($product->stock < $product->low_stock_threshold) {
                        event(new LowStockAlert($product));
                    }
                    
                    // Si se agotó, notificar
                    if ($product->stock <= 0) {
                        event(new ProductOutOfStock($product));
                    }
                }
                */
            }

            // También podrías:
            
            // 1. Marcar productos como "reservados" durante el pago
            //    y confirmar la reserva aquí
            
            // 2. Activar servicios/suscripciones
            /*
            if ($event->metadata['type'] === 'subscription') {
                Subscription::create([
                    'user_id' => $event->metadata['user_id'],
                    'plan' => $event->metadata['plan'],
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                ]);
            }
            */
            
            // 3. Generar códigos de descarga para productos digitales
            /*
            if ($event->metadata['type'] === 'digital') {
                DownloadCode::create([
                    'order_id' => $event->orderId,
                    'product_id' => $productId,
                    'code' => Str::random(32),
                    'expires_at' => now()->addDays(7),
                ]);
            }
            */

        } catch (\Exception $e) {
            Log::error('Failed to update inventory', [
                'order_id' => $event->orderId,
                'error' => $e->getMessage(),
            ]);
            
            // Esto es crítico: si falla, podríamos vender más de lo disponible
            // Considerar re-intentos o notificación de emergencia
        }
    }
}
