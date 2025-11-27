# 🎯 Sistema de Eventos para Pagos en Laravel

Este documento explica cómo funciona el sistema de eventos implementado para manejar las acciones post-pago de manera uniforme, independientemente del proveedor de pago utilizado.

---

## 📚 Índice

1. [¿Qué Problema Resuelve?](#qué-problema-resuelve)
2. [Arquitectura](#arquitectura)
3. [Componentes](#componentes)
4. [Flujo de Ejecución](#flujo-de-ejecución)
5. [Ventajas](#ventajas)
6. [Uso](#uso)
7. [Personalización](#personalización)
8. [Testing](#testing)

---

## 🤔 ¿Qué Problema Resuelve?

### Antes (Sin Eventos)

```php
// En cada controlador de pago
public function stripeReturn(Request $request)
{
    $result = $gateway->capture($request->payment_intent);
    
    if ($result->success) {
        // ❌ Código duplicado en cada proveedor
        Payment::create([...]); // Guardar en BD
        Mail::to($customer)->send(new PaymentConfirmation()); // Email
        Notification::send($admin, new PaymentReceived()); // Notificar admin
        Inventory::reduce($orderId); // Actualizar inventario
        // ... más acciones
        
        return view('success');
    }
}

public function redsysReturn(Request $request)
{
    $result = $gateway->verifyCallback($request->all());
    
    if ($result->success) {
        // ❌ Mismo código otra vez
        Payment::create([...]); 
        Mail::to($customer)->send(new PaymentConfirmation());
        Notification::send($admin, new PaymentReceived());
        Inventory::reduce($orderId);
        // ... más acciones
        
        return view('success');
    }
}

// Y así con cada proveedor... 😫
```

**Problemas:**
- ❌ Código duplicado en cada proveedor
- ❌ Difícil de mantener (cambiar algo = tocar todos los proveedores)
- ❌ Mezcla lógica de negocio con lógica de pago
- ❌ No hay separación de responsabilidades
- ❌ Imposible reutilizar lógica

---

### Ahora (Con Eventos)

```php
// En cada controlador de pago
public function stripeReturn(Request $request)
{
    $result = $gateway->capture($request->payment_intent);
    
    if ($result->success) {
        // ✅ Una sola línea
        event(new PaymentCompleted($provider, $result, ...));
        
        return view('success');
    }
}

public function redsysReturn(Request $request)
{
    $result = $gateway->verifyCallback($request->all());
    
    if ($result->success) {
        // ✅ Misma línea, diferente proveedor
        event(new PaymentCompleted($provider, $result, ...));
        
        return view('success');
    }
}
```

**Ventajas:**
- ✅ Sin duplicación
- ✅ Fácil de mantener
- ✅ Separación de responsabilidades
- ✅ Lógica reutilizable
- ✅ Fácil añadir/quitar acciones

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────┐
│                   PAGO COMPLETADO                       │
│              (Stripe/Redsys/PayPal/etc.)                │
└──────────────────────┬──────────────────────────────────┘
                       │
                       ▼
              ┌────────────────┐
              │ PaymentCompleted │ ◄──── EVENTO
              │     (Event)      │
              └────────┬─────────┘
                       │
         ┌─────────────┴──────────────┐
         │  AppServiceProvider.boot() │
         │   Event::listen(...)       │
         └─────────────┬──────────────┘
                       │
        ┌──────────────┴───────────────┐
        │                              │
        ▼                              ▼
┌───────────────┐              ┌────────────────┐
│   LISTENERS   │              │   LISTENERS    │
│  (Síncronos)  │              │  (Asíncronos)  │
└───────┬───────┘              └────────┬───────┘
        │                               │
        ▼                               ▼
┌─────────────────────┐      ┌──────────────────────┐
│ LogPaymentToDatabase│      │ SendConfirmationEmail│
│ (Crítico - Inmediato)│      │  (Queue - Background)│
└─────────────────────┘      └──────────────────────┘
                              
        ▼                               ▼
┌─────────────────────┐      ┌──────────────────────┐
│  UpdateInventory    │      │ SendAdminNotification│
│ (Crítico - Inmediato)│      │  (Queue - Background)│
└─────────────────────┘      └──────────────────────┘
```

---

## 🧩 Componentes

### 1. Evento: `PaymentCompleted`

**Ubicación:** `app/Events/PaymentCompleted.php`

**Propósito:** Encapsula toda la información de un pago completado, independiente del proveedor.

```php
class PaymentCompleted
{
    public function __construct(
        public readonly PaymentProvider $provider,    // STRIPE, REDSYS, PAYPAL
        public readonly PaymentResult $result,        // Resultado del pago
        public readonly string $orderId,              // ID de la orden
        public readonly float $amount,                // Cantidad pagada
        public readonly string $currency,             // Moneda (EUR, USD)
        public readonly array $metadata = [],         // Datos adicionales
        public readonly ?string $customerEmail = null // Email del cliente
    ) {}
}
```

**Características:**
- ✅ Agnóstico del proveedor (funciona con todos)
- ✅ Contiene toda la info necesaria
- ✅ Inmutable (`readonly`)
- ✅ Tipado fuerte

---

### 2. Listeners (Escuchadores)

Los listeners son clases que se ejecutan cuando se dispara el evento `PaymentCompleted`.

#### 2.1. `LogPaymentToDatabase` ⚡ Síncrono

**Propósito:** Guardar el pago en la base de datos.

```php
class LogPaymentToDatabase
{
    public function handle(PaymentCompleted $event): void
    {
        // Guardar pago en BD
        Payment::create([
            'order_id' => $event->orderId,
            'payment_id' => $event->result->paymentId,
            'provider' => $event->provider,
            'amount' => $event->amount,
            'state' => $event->result->state,
            // ...
        ]);
    }
}
```

**Características:**
- ⚡ Se ejecuta **inmediatamente** (síncrono)
- 🔒 **Crítico** - debe completarse antes de mostrar éxito
- 💾 Sin esto, perdemos el registro del pago

---

#### 2.2. `SendPaymentConfirmationEmail` 📧 Asíncrono

**Propósito:** Enviar email de confirmación al cliente.

```php
class SendPaymentConfirmationEmail
{
    public function handle(PaymentCompleted $event): void
    {
        if (!$event->customerEmail) return;
        
        Mail::to($event->customerEmail)
            ->send(new PaymentConfirmationMail($event));
    }
    
    public function shouldQueue(): bool
    {
        return true; // ✅ Ejecutar en background
    }
}
```

**Características:**
- ⏱️ Se ejecuta en **background** (asíncrono)
- 📧 No bloquea la respuesta al usuario
- ♻️ Puede reintentar si falla

---

#### 2.3. `SendAdminNotification` 📢 Asíncrono

**Propósito:** Notificar al administrador.

```php
class SendAdminNotification
{
    public function handle(PaymentCompleted $event): void
    {
        $admin = config('payments.admin_email');
        
        // Email, Slack, SMS, etc.
        Notification::route('slack', config('slack.webhook'))
            ->notify(new PaymentReceivedNotification($event));
    }
    
    public function shouldQueue(): bool
    {
        return true; // ✅ Ejecutar en background
    }
}
```

**Características:**
- 📢 Multi-canal (email, Slack, SMS)
- ⏱️ Background (no bloquea)
- 🔔 Puede incluir métricas, gráficas, etc.

---

#### 2.4. `UpdateInventory` 📦 Síncrono

**Propósito:** Actualizar inventario, activar suscripciones, etc.

```php
class UpdateInventory
{
    public function handle(PaymentCompleted $event): void
    {
        $items = $event->metadata['items'] ?? [];
        
        foreach ($items as $item) {
            Product::find($item['product_id'])
                ->decrement('stock', $item['quantity']);
        }
    }
}
```

**Características:**
- ⚡ Síncrono (crítico)
- 📦 Evita sobre-ventas
- 🔔 Puede disparar más eventos (`LowStockAlert`)

---

### 3. Registro de Eventos

**Ubicación:** `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    Event::listen(
        PaymentCompleted::class,
        [
            LogPaymentToDatabase::class,        // 1️⃣ Primero: Guardar
            SendPaymentConfirmationEmail::class, // 2️⃣ Email cliente
            SendAdminNotification::class,        // 3️⃣ Notificar admin
            UpdateInventory::class,              // 4️⃣ Inventario
        ]
    );
}
```

**Orden de ejecución:**
1. Listeners síncronos se ejecutan en orden
2. Listeners asíncronos se añaden a la cola
3. Si un listener falla, los demás continúan (salvo que se lance excepción)

---

## 🔄 Flujo de Ejecución

### Ejemplo: Pago con Stripe

```php
// 1️⃣ Usuario completa pago en Stripe
POST /payments/stripe/verify
{
    "payment_intent": "pi_xxx",
    "amount": 50.00,
    "customer_email": "cliente@example.com"
}

// 2️⃣ Controller captura el pago
$gateway = $paymentManager->driver(PaymentProvider::STRIPE);
$result = $gateway->capture($paymentIntent);

// 3️⃣ Si exitoso, disparar evento
if ($result->success) {
    event(new PaymentCompleted(
        provider: PaymentProvider::STRIPE,
        result: $result,
        orderId: $paymentIntent,
        amount: 50.00,
        currency: 'EUR',
        customerEmail: 'cliente@example.com'
    ));
    
    return response()->json(['success' => true]);
}

// 4️⃣ Laravel ejecuta los listeners
// ⚡ Síncrono (inmediato):
LogPaymentToDatabase::handle()  // Guarda en BD
UpdateInventory::handle()       // Reduce stock

// ⏱️ Asíncrono (cola):
Queue::push(SendPaymentConfirmationEmail::handle()) // Email
Queue::push(SendAdminNotification::handle())        // Notificación

// 5️⃣ Usuario recibe respuesta inmediata
{
    "success": true,
    "message": "Payment successful!"
}

// 6️⃣ Workers procesan cola en background
// (segundos después)
php artisan queue:work
  → SendPaymentConfirmationEmail executed
  → SendAdminNotification executed
```

---

## ✨ Ventajas

### 1. **Agnóstico del Proveedor**

```php
// ✅ Mismo evento para TODOS los proveedores
event(new PaymentCompleted(...)); // Stripe
event(new PaymentCompleted(...)); // Redsys
event(new PaymentCompleted(...)); // PayPal
event(new PaymentCompleted(...)); // Cualquier proveedor futuro
```

No importa si el pago vino de Stripe, Redsys o PayPal. Las acciones post-pago son las mismas.

---

### 2. **Fácil Añadir Nuevas Acciones**

¿Quieres enviar una notificación a Discord cuando haya un pago?

```bash
# 1. Crear listener
php artisan make:listener SendDiscordNotification --event=PaymentCompleted
```

```php
// 2. Implementar
class SendDiscordNotification
{
    public function handle(PaymentCompleted $event): void
    {
        Http::post(config('services.discord.webhook'), [
            'content' => "💰 Nuevo pago: €{$event->amount}"
        ]);
    }
    
    public function shouldQueue(): bool { return true; }
}
```

```php
// 3. Registrar en AppServiceProvider
Event::listen(PaymentCompleted::class, [
    // ... listeners existentes
    SendDiscordNotification::class, // ← Nueva acción
]);
```

**¡Listo!** Sin tocar ningún controlador. Sin modificar código de proveedores. ✅

---

### 3. **Fácil Desactivar Acciones**

¿No quieres notificaciones al admin en desarrollo?

```php
// En AppServiceProvider
Event::listen(PaymentCompleted::class, [
    LogPaymentToDatabase::class,
    SendPaymentConfirmationEmail::class,
    // SendAdminNotification::class, ← Comentar para desactivar
    UpdateInventory::class,
]);
```

---

### 4. **Ejecución Condicional**

Puedes ejecutar acciones solo bajo ciertas condiciones:

```php
class SendPremiumGift
{
    public function handle(PaymentCompleted $event): void
    {
        // Solo si el pago es >= 100€
        if ($event->amount < 100) return;
        
        // Enviar regalo
        Gift::create([
            'order_id' => $event->orderId,
            'type' => 'premium_bonus',
        ]);
    }
}
```

---

### 5. **Testing Simplificado**

```php
// Test: Verificar que se dispara el evento
public function test_payment_completed_event_is_dispatched()
{
    Event::fake([PaymentCompleted::class]);
    
    // Completar pago
    $this->post('/payments/stripe/verify', [
        'payment_intent' => 'pi_test',
        'amount' => 50.00,
    ]);
    
    // Verificar que se disparó
    Event::assertDispatched(PaymentCompleted::class);
}

// Test: Verificar que se ejecutan listeners
public function test_payment_saves_to_database()
{
    Event::fake();
    
    event(new PaymentCompleted(...));
    
    // Verificar que se guardó
    $this->assertDatabaseHas('payments', [
        'order_id' => 'ORDER-123',
    ]);
}
```

---

### 6. **Monitoreo y Logging**

Puedes crear un listener solo para logging:

```php
class LogPaymentMetrics
{
    public function handle(PaymentCompleted $event): void
    {
        // Métricas
        Metrics::increment('payments.completed');
        Metrics::gauge('payments.amount', $event->amount);
        Metrics::tag('payments.provider', $event->provider->value);
        
        // Analytics
        Analytics::track('Payment Completed', $event->toArray());
        
        // APM (New Relic, DataDog, etc.)
        Apm::recordTransaction('payment.completed', $event->amount);
    }
}
```

---

## 🚀 Uso

### Disparar el Evento

```php
use App\Events\PaymentCompleted;
use App\Enums\PaymentProvider;

// Cuando un pago se complete exitosamente
event(new PaymentCompleted(
    provider: PaymentProvider::STRIPE,
    result: $paymentResult,
    orderId: 'ORDER-123',
    amount: 99.99,
    currency: 'EUR',
    metadata: [
        'items' => [
            ['product_id' => 1, 'quantity' => 2],
            ['product_id' => 5, 'quantity' => 1],
        ],
        'user_id' => 42,
        'type' => 'subscription',
    ],
    customerEmail: 'cliente@example.com'
));
```

### Datos Disponibles en Listeners

```php
class MiListener
{
    public function handle(PaymentCompleted $event): void
    {
        $event->provider;      // STRIPE, REDSYS, PAYPAL
        $event->result;        // PaymentResult (paymentId, state, data)
        $event->orderId;       // "ORDER-123"
        $event->amount;        // 99.99
        $event->currency;      // "EUR"
        $event->metadata;      // Array con datos extra
        $event->customerEmail; // "cliente@example.com" o null
        
        // Método helper
        $event->toArray();     // Convierte todo a array
    }
}
```

---

## 🎨 Personalización

### Añadir Más Listeners

```bash
# Crear nuevo listener
php artisan make:listener NombreListener --event=PaymentCompleted
```

### Listener Síncrono (Inmediato)

```php
class MiListenerSincrono
{
    public function handle(PaymentCompleted $event): void
    {
        // Código que se ejecuta INMEDIATAMENTE
        // Bloquea la respuesta al usuario hasta completarse
    }
}
```

### Listener Asíncrono (Cola)

```php
class MiListenerAsincrono implements ShouldQueue
{
    public function handle(PaymentCompleted $event): void
    {
        // Código que se ejecuta EN BACKGROUND
        // No bloquea la respuesta al usuario
    }
}
```

### Registrar Listener

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(PaymentCompleted::class, [
        // ... existentes
        MiListenerPersonalizado::class,
    ]);
}
```

---

## 🧪 Testing

### Fake Events (No ejecutar listeners)

```php
use Illuminate\Support\Facades\Event;

public function test_example()
{
    Event::fake([PaymentCompleted::class]);
    
    // Código que dispara el evento
    $this->post('/payments/stripe/verify', [...]);
    
    // Verificar que se disparó
    Event::assertDispatched(PaymentCompleted::class);
    
    // Verificar datos del evento
    Event::assertDispatched(
        PaymentCompleted::class,
        function ($event) {
            return $event->amount === 50.00
                && $event->provider === PaymentProvider::STRIPE;
        }
    );
}
```

### Ejecutar Solo Algunos Listeners

```php
Event::fake([
    PaymentCompleted::class => [
        LogPaymentToDatabase::class, // ← Solo este se ejecuta
    ]
]);
```

---

## 📋 Checklist de Implementación

- [x] ✅ Evento `PaymentCompleted` creado
- [x] ✅ Listener `LogPaymentToDatabase` creado
- [x] ✅ Listener `SendPaymentConfirmationEmail` creado
- [x] ✅ Listener `SendAdminNotification` creado
- [x] ✅ Listener `UpdateInventory` creado
- [x] ✅ Listeners registrados en `AppServiceProvider`
- [x] ✅ Controllers actualizados para disparar evento
- [ ] ⏳ Configurar colas en producción (`queue:work`)
- [ ] ⏳ Crear modelos Eloquent (`Payment`, `Order`, etc.)
- [ ] ⏳ Implementar envío real de emails
- [ ] ⏳ Configurar Slack/Discord webhooks
- [ ] ⏳ Tests unitarios para listeners

---

## 🔗 Referencias

- [Documentación Laravel Events](https://laravel.com/docs/events)
- [Laravel Queues](https://laravel.com/docs/queues)
- [README_EVENT_SYSTEM.md](../README_EVENT_SYSTEM.md) - Comparativa Event System vs Service Layer

---

## 🎯 Próximos Pasos

1. **Configurar Queue Worker** en producción:
   ```bash
   php artisan queue:work --daemon
   ```

2. **Crear Mailable** para email de confirmación:
   ```bash
   php artisan make:mail PaymentConfirmationMail
   ```

3. **Crear Notification** para Slack/Discord:
   ```bash
   php artisan make:notification PaymentReceivedNotification
   ```

4. **Implementar Models** (`Payment`, `Order`, `Product`):
   ```bash
   php artisan make:model Payment -m
   php artisan make:model Order -m
   ```

5. **Testing**:
   ```bash
   php artisan make:test PaymentEventsTest
   ```

---

**¡Sistema de Eventos Implementado!** 🎉

Ahora todas las acciones post-pago se ejecutan automáticamente, sin importar el proveedor de pago utilizado.

