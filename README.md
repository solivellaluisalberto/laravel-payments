# 💳 Sistema de Pagos Multi-Proveedor - Laravel

Sistema profesional de pagos construido con **Laravel 11**, implementando arquitectura limpia con **DTOs**, **Strategy Pattern**, **Factory Pattern** y **Event System**. Soporta **Stripe**, **Redsys** y **PayPal**.

---

## 🚀 Inicio Rápido

### 1. Clonar e Instalar

```bash
# Navegar al proyecto
cd test_payments

# Instalar dependencias (si es necesario)
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate
```

### 2. Configurar Variables de Entorno

Edita el archivo `.env` y añade tus credenciales:

```env
# Stripe
STRIPE_SECRET_KEY=sk_test_tu_clave_aqui
STRIPE_PUBLIC_KEY=pk_test_tu_clave_aqui

# Redsys
REDSYS_MERCHANT_CODE=999008881
REDSYS_SECRET_KEY=sq7HjrUOBfKmC576ILgskD5srU870gJ7
REDSYS_TERMINAL=1
REDSYS_ENVIRONMENT=test

# PayPal
PAYPAL_CLIENT_ID=tu_client_id_aqui
PAYPAL_CLIENT_SECRET=tu_client_secret_aqui
PAYPAL_ENVIRONMENT=sandbox

# Email del administrador (para notificaciones)
PAYMENT_ADMIN_EMAIL=admin@example.com
```

Ver guía completa: [`config/payments.example.env`](config/payments.example.env)

### 3. Iniciar Servidor

```bash
php artisan serve
```

Accede a: **http://localhost:8000**

---

## 📁 Estructura del Proyecto

```
test_payments/
├── app/
│   ├── DTOs/                      # Data Transfer Objects
│   │   ├── PaymentRequest.php
│   │   ├── PaymentResponse.php
│   │   └── PaymentResult.php
│   │
│   ├── Enums/                     # Enumeraciones
│   │   ├── PaymentProvider.php
│   │   ├── PaymentMethod.php
│   │   ├── PaymentState.php
│   │   └── PaymentType.php
│   │
│   ├── Services/Payments/         # Servicios de Pago
│   │   ├── PaymentGateway.php        (interface)
│   │   ├── PaymentManager.php        (factory)
│   │   ├── StripePaymentService.php
│   │   ├── RedsysPaymentService.php
│   │   └── PayPalPaymentService.php
│   │
│   ├── Events/                    # Eventos
│   │   └── PaymentCompleted.php
│   │
│   ├── Listeners/                 # Listeners de Eventos
│   │   ├── LogPaymentToDatabase.php
│   │   ├── SendPaymentConfirmationEmail.php
│   │   ├── SendAdminNotification.php
│   │   └── UpdateInventory.php
│   │
│   ├── Http/Controllers/          # Controladores
│   │   └── PaymentController.php
│   │
│   └── Providers/
│       └── AppServiceProvider.php  (registro de eventos)
│
├── config/
│   ├── payments.php               # Configuración de pagos
│   └── payments.example.env       # Plantilla de .env
│
├── resources/views/
│   ├── layouts/
│   │   └── app.blade.php          # Layout principal
│   └── payments/                  # Vistas de pago
│       ├── index.blade.php           (inicio)
│       ├── stripe.blade.php          (Stripe)
│       ├── redsys.blade.php          (Redsys)
│       ├── paypal.blade.php          (PayPal)
│       ├── refund.blade.php          (Reembolsos)
│       ├── comparative.blade.php     (Comparativa)
│       ├── events.blade.php          (Doc. Eventos)
│       ├── success.blade.php         (Pago exitoso)
│       ├── error.blade.php           (Error)
│       └── cancelled.blade.php       (Cancelado)
│
├── routes/
│   └── web.php                    # Rutas de la aplicación
│
└── EVENTOS_LARAVEL.md             # Documentación del Event System
```

---

## ✨ Características

### 🎯 Core

| Característica | Descripción |
|---|---|
| **Laravel 11** | Framework moderno y potente |
| **Multi-proveedor** | Stripe, Redsys, PayPal |
| **DTOs** | Transferencia de datos tipada y segura |
| **Strategy Pattern** | Fácil añadir nuevos proveedores |
| **Factory Pattern** | PaymentManager con cache de instancias |
| **Event System** | Acciones post-pago automáticas |
| **Blade Templates** | Vistas modernas y reutilizables |
| **Type Safety** | PHP 8.2+ con tipos estrictos |

### 📢 Sistema de Eventos

| Evento | Descripción |
|---|---|
| **PaymentCompleted** | Se dispara cuando un pago se completa exitosamente |

| Listener | Tipo | Función |
|---|---|---|
| **LogPaymentToDatabase** | Síncrono | Guarda el pago en BD |
| **SendPaymentConfirmationEmail** | Asíncrono | Email al cliente |
| **SendAdminNotification** | Asíncrono | Notifica al admin |
| **UpdateInventory** | Síncrono | Actualiza stock/inventario |

**Ventajas:**
- ✅ Código común para todos los proveedores
- ✅ Fácil añadir nuevas acciones sin tocar controllers
- ✅ Listeners asíncronos no bloquean la respuesta
- ✅ Testing simplificado con `Event::fake()`

Ver documentación completa: [`EVENTOS_LARAVEL.md`](EVENTOS_LARAVEL.md)

---

## 🎯 Proveedores Soportados

### 💳 Stripe
- **Flujo:** API (sin redirección)
- **Integración:** Payment Intents + Stripe.js
- **Ventajas:** UX excelente, sin salir del sitio
- **Reembolsos:** Automáticos vía API
- **Ruta:** `/payments/stripe`

### 🏦 Redsys
- **Flujo:** Redirección al TPV del banco
- **Integración:** Formulario firmado
- **Métodos:** Tarjeta, Bizum
- **Reembolsos:** API REST (TransactionType: 3)
- **Ruta:** `/payments/redsys`

### 💰 PayPal
- **Flujo:** Redirección a PayPal
- **Integración:** SDK oficial (`paypal/paypal-checkout-sdk`)
- **Ventajas:** Marca reconocida globalmente
- **Reembolsos:** API REST
- **Ruta:** `/payments/paypal`

---

## 🛣️ Rutas Disponibles

### Principal
```
GET  /                          → Página de inicio con ejemplos
GET  /payments/comparative      → Comparativa de proveedores
GET  /payments/events           → Documentación del Event System
```

### Stripe
```
GET  /payments/stripe           → Formulario de pago
POST /payments/stripe/initiate  → Crear Payment Intent
POST /payments/stripe/verify    → Verificar pago (dispara evento)
```

### Redsys
```
GET  /payments/redsys           → Formulario de pago
POST /payments/redsys/initiate  → Generar formulario firmado
ANY  /payments/redsys/return    → Callback de retorno (dispara evento)
GET  /payments/redsys/cancel    → Cancelación
```

### PayPal
```
GET  /payments/paypal           → Formulario de pago
POST /payments/paypal/initiate  → Crear orden PayPal
GET  /payments/paypal/return    → Callback de retorno (dispara evento)
GET  /payments/paypal/cancel    → Cancelación
```

### Reembolsos
```
GET  /payments/refund           → Formulario de reembolso
POST /payments/refund/process   → Procesar reembolso
```

---

## 🏗️ Arquitectura

### 📦 DTOs (Data Transfer Objects)

Objetos inmutables para transferencia de datos entre capas:

```php
// Request unificado para todos los proveedores
PaymentRequest(
    float $amount,
    string $currency,
    string $orderId,
    array $metadata = [],
    ?string $returnUrl = null,
    ?string $cancelUrl = null,
    ?PaymentMethod $paymentMethod = null
)

// Response adaptada al tipo de flujo
PaymentResponse(
    PaymentType $type,              // API o REDIRECT
    array $data,
    ?string $redirectUrl = null,
    ?string $formHtml = null,
    ?string $clientSecret = null
)

// Resultado de operaciones
PaymentResult(
    bool $success,
    string $status,
    ?string $paymentId = null,
    ?string $transactionId = null,
    ?string $message = null,
    array $data = []
)
```

### ⚙️ Strategy Pattern

```php
interface PaymentGateway
{
    public function initiate(PaymentRequest $request): PaymentResponse;
    public function capture(string $paymentId): PaymentResult;
    public function refund(string $paymentId, ?float $amount = null): PaymentResult;
    public function getStatus(string $paymentId): PaymentResult;
    public function verifyCallback(array $postData): PaymentResult;
}
```

Cada proveedor implementa `PaymentGateway` con su lógica específica.

### 🏭 Factory Pattern

```php
class PaymentManager
{
    private array $gateways = [];  // Cache de instancias
    
    public function driver(PaymentProvider $provider): PaymentGateway
    {
        // Retorna instancia cacheada o crea nueva
        return $this->gateways[$provider->value] ??= match($provider) {
            PaymentProvider::STRIPE => $this->createStripeGateway(),
            PaymentProvider::REDSYS => $this->createRedsysGateway(),
            PaymentProvider::PAYPAL => $this->createPayPalGateway(),
        };
    }
}
```

### 📢 Event System

```php
// En el Controller, cuando el pago se completa
if ($result->success) {
    event(new PaymentCompleted(
        provider: PaymentProvider::STRIPE,
        result: $result,
        orderId: $orderId,
        amount: $amount,
        currency: 'EUR',
        customerEmail: $customerEmail
    ));
}

// En AppServiceProvider
Event::listen(PaymentCompleted::class, [
    LogPaymentToDatabase::class,
    SendPaymentConfirmationEmail::class,
    SendAdminNotification::class,
    UpdateInventory::class,
]);
```

**Ventaja:** El mismo evento funciona para TODOS los proveedores.

---

## 🎓 Cómo Funciona

### Ejemplo: Pago con Stripe

1. **Usuario** accede a `/payments/stripe`
2. **Vista Blade** muestra formulario con Stripe.js
3. **JavaScript** envía datos a Stripe (seguro)
4. **Controller** crea Payment Intent
5. **Frontend** confirma con Stripe
6. **Controller** verifica pago
7. **Evento** `PaymentCompleted` se dispara
8. **Listeners** ejecutan acciones automáticamente:
   - ⚡ Guardar en BD
   - ⚡ Actualizar inventario
   - ⏱️ Enviar email (cola)
   - ⏱️ Notificar admin (cola)

### Ejemplo: Pago con Redsys

1. **Usuario** accede a `/payments/redsys`
2. **Vista Blade** muestra formulario
3. **Controller** genera formulario firmado
4. **Usuario** es redirigido al TPV del banco
5. **Usuario** introduce datos de tarjeta
6. **Banco** procesa pago
7. **Callback** a `/payments/redsys/return`
8. **Controller** verifica firma
9. **Evento** `PaymentCompleted` se dispara
10. **Listeners** ejecutan acciones automáticamente

---

## 🔐 Seguridad

### ✅ Implementado

- ✅ Variables de entorno con `.env`
- ✅ `.gitignore` completo (incluye `.env`, claves, etc.)
- ✅ Sin credenciales hardcoded
- ✅ Validación estricta en servicios
- ✅ CSRF protection (Laravel)
- ✅ Validación de firmas (Redsys)
- ✅ Inyección de dependencias

### ⚠️ Antes de Producción

1. ✅ Revocar claves de test
2. ✅ Usar claves `live` en `.env`
3. ✅ Activar HTTPS
4. ✅ Configurar webhooks
5. ✅ Configurar colas (Redis/Database)
6. ✅ Revisar listeners asíncronos
7. ✅ Implementar modelos Eloquent reales
8. ✅ Configurar emails reales

---

## 📧 Emails y Notificaciones

### Actual (Desarrollo)

Los listeners **simulan** el envío usando `Log::info()`:

```php
// Listeners actuales
Log::info('📧 Payment confirmation email sent', $emailData);
Log::info('📢 Admin notification sent', $notificationData);
```

### Para Producción

Implementar emails reales:

```bash
# 1. Crear Mailable
php artisan make:mail PaymentConfirmationMail

# 2. Usar en el Listener
Mail::to($event->customerEmail)
    ->send(new PaymentConfirmationMail($event));
```

Notificaciones multi-canal:

```php
// Email
Mail::to($adminEmail)->send(...);

// Slack
Notification::route('slack', config('services.slack.webhook'))
    ->notify(new PaymentNotification($event));

// SMS (Twilio, etc.)
// WhatsApp, Discord, etc.
```

---

## 📦 Base de Datos

### Actual (Desarrollo)

Los listeners **simulan** el guardado usando `Log::info()`:

```php
Log::info('💾 Payment logged to database', $paymentData);
```

### Para Producción

Implementar modelos Eloquent:

```bash
# 1. Crear modelos
php artisan make:model Payment -m
php artisan make:model Order -m

# 2. Migrar
php artisan migrate

# 3. Usar en Listeners
Payment::create([
    'order_id' => $event->orderId,
    'payment_id' => $event->result->paymentId,
    'provider' => $event->provider,
    'amount' => $event->amount,
    'completed_at' => now(),
]);
```

---

## ⚙️ Colas (Queues)

### Configuración

Los listeners asíncronos están marcados con `shouldQueue()`:

```php
class SendPaymentConfirmationEmail
{
    public function shouldQueue(): bool
    {
        return true;  // Se ejecuta en background
    }
}
```

### Ejecutar Workers

```bash
# Desarrollo
php artisan queue:work

# Producción (con supervisor o Laravel Forge)
php artisan queue:work --daemon --tries=3
```

---

## 🧪 Testing

### Tarjetas de Prueba

**Stripe:**
```
Éxito:   4242 4242 4242 4242
Rechazo: 4000 0000 0000 0002
```

**Redsys:**
```
Tarjeta: 4548 8120 4940 0004
CVV: 123
CIP: 123456
```

**PayPal:**
Crear cuenta en: https://developer.paypal.com/dashboard/accounts

### Testing con Eventos

```php
// Test: Verificar que el evento se dispara
Event::fake([PaymentCompleted::class]);

$this->post('/payments/stripe/verify', [
    'payment_intent' => 'pi_test',
    'amount' => 50.00,
]);

Event::assertDispatched(PaymentCompleted::class);
```

---

## 🎓 Añadir Nuevo Proveedor

### Paso 1: Crear Service

```bash
touch app/Services/Payments/MercadoPagoService.php
```

```php
class MercadoPagoService implements PaymentGateway
{
    public function initiate(PaymentRequest $request): PaymentResponse { }
    public function capture(string $paymentId): PaymentResult { }
    public function refund(string $paymentId, ?float $amount): PaymentResult { }
    public function getStatus(string $paymentId): PaymentResult { }
    public function verifyCallback(array $postData): PaymentResult { }
}
```

### Paso 2: Añadir al Enum

```php
// app/Enums/PaymentProvider.php
enum PaymentProvider: string
{
    case STRIPE = 'stripe';
    case REDSYS = 'redsys';
    case PAYPAL = 'paypal';
    case MERCADOPAGO = 'mercadopago';  // ← Nuevo
}
```

### Paso 3: Registrar en Manager

```php
// app/Services/Payments/PaymentManager.php
public function driver(PaymentProvider $provider): PaymentGateway
{
    return match($provider) {
        PaymentProvider::STRIPE => $this->createStripeGateway(),
        PaymentProvider::REDSYS => $this->createRedsysGateway(),
        PaymentProvider::PAYPAL => $this->createPayPalGateway(),
        PaymentProvider::MERCADOPAGO => $this->createMercadoPagoGateway(),
    };
}
```

### Paso 4: Configuración

```php
// config/payments.php
'mercadopago' => [
    'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
],
```

**¡Listo!** El nuevo proveedor ya funciona con:
- ✅ DTOs
- ✅ PaymentManager
- ✅ Event System
- ✅ Todos los listeners

---

## 📊 Comparación de Flujos

| Proveedor | Flujo | Integración | UX | Reembolsos |
|---|---|---|---|---|
| **Stripe** | API (mismo sitio) | client_secret + Stripe.js | ⭐⭐⭐⭐⭐ | Automáticos |
| **Redsys** | Redirección (TPV) | formHtml firmado | ⭐⭐⭐ | API REST |
| **PayPal** | Redirección | SDK oficial | ⭐⭐⭐⭐ | API REST |

---

## 📖 Documentación Adicional

| Archivo | Descripción |
|---|---|
| [`EVENTOS_LARAVEL.md`](EVENTOS_LARAVEL.md) | **Guía completa del Event System** |
| [`config/payments.example.env`](config/payments.example.env) | Plantilla de configuración |

### Documentación en el Navegador

Una vez iniciado el servidor, accede a:

- **Inicio:** http://localhost:8000
- **Comparativa:** http://localhost:8000/payments/comparative
- **Eventos:** http://localhost:8000/payments/events

---

## ⚠️ Problemas Conocidos

### PayPal SDK Deprecation Warnings

El SDK de PayPal genera warnings con PHP 8.2+. Es un bug del SDK.

**Solución temporal:** Los ejemplos incluyen `error_reporting()` para suprimir warnings.

**Solución permanente:** Usar `paypal/paypal-checkout-sdk` (ya implementado).

---

## 🔄 Próximos Pasos

### Implementar en Producción

- [ ] Configurar claves `live` en `.env`
- [ ] Activar HTTPS
- [ ] Configurar webhooks
- [ ] Implementar modelos Eloquent
- [ ] Configurar Redis para colas
- [ ] Implementar emails reales
- [ ] Testing completo
- [ ] Configurar monitoring (Sentry, Bugsnag, etc.)

### Mejoras Opcionales

- [ ] Panel de administración
- [ ] Reportes y métricas
- [ ] Suscripciones recurrentes
- [ ] Multi-moneda
- [ ] Internacionalización (i18n)

---

## 📞 Recursos

### Laravel
- [Documentación oficial](https://laravel.com/docs)
- [Events & Listeners](https://laravel.com/docs/events)
- [Queues](https://laravel.com/docs/queues)

### Proveedores de Pago
- **Stripe:** [Docs](https://stripe.com/docs) | [Dashboard](https://dashboard.stripe.com)
- **Redsys:** [Manual](https://pagosonline.redsys.es/desarrolladores.html)
- **PayPal:** [Docs](https://developer.paypal.com/docs/) | [Dashboard](https://developer.paypal.com/dashboard/)

---

## 📄 Licencia

Proyecto educativo. Usa bajo tu responsabilidad.

---

## 🎉 Resumen

Este proyecto es un **sistema de pagos profesional** construido con Laravel que demuestra:

✅ **Arquitectura limpia** con DTOs, Strategy Pattern y Factory Pattern
✅ **Event System** para acciones post-pago desacopladas
✅ **Multi-proveedor** (Stripe, Redsys, PayPal)
✅ **Type Safety** con PHP 8.2+
✅ **Blade Templates** modernos y responsive
✅ **Testing** simplificado con Event::fake()
✅ **Documentación** completa y ejemplos funcionales

**💡 TIP:** Empieza explorando http://localhost:8000 para ver todos los ejemplos funcionando.

---

**🚀 ¡Disfruta construyendo tu sistema de pagos!**
