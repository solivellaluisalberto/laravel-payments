# 💳 Sistema de Pagos Multi-Proveedor - Laravel

Sistema profesional de pagos migrado desde PHP vanilla a Laravel, con arquitectura DTOs, Strategy Pattern y Factory Pattern.

## 🚀 Inicio Rápido

### 1. Configurar Variables de Entorno

Copia las variables de entorno necesarias a tu archivo `.env`:

```env
# Stripe
STRIPE_SECRET_KEY=sk_test_tu_clave_aqui
STRIPE_PUBLIC_KEY=pk_test_tu_clave_aqui
STRIPE_WEBHOOK_SECRET=whsec_tu_webhook_secret_aqui

# Redsys
REDSYS_MERCHANT_CODE=999008881
REDSYS_SECRET_KEY=sq7HjrUOBfKmC576ILgskD5srU870gJ7
REDSYS_TERMINAL=1
REDSYS_ENVIRONMENT=test

# PayPal
PAYPAL_CLIENT_ID=tu_client_id_aqui
PAYPAL_CLIENT_SECRET=tu_client_secret_aqui
PAYPAL_ENVIRONMENT=sandbox
```

### 2. Instalar Dependencias

Las dependencias ya están instaladas, pero si necesitas reinstalarlas:

```bash
cd test_payments
composer require stripe/stripe-php sermepa/sermepa paypal/paypal-checkout-sdk
```

### 3. Acceder a la Aplicación

```bash
# Iniciar servidor de desarrollo
php artisan serve

# La aplicación estará disponible en:
# http://localhost:8000
```

---

## 📁 Estructura del Proyecto

```
app/
├── DTOs/                           # Data Transfer Objects
│   ├── PaymentRequest.php         # Solicitud de pago
│   ├── PaymentResponse.php        # Respuesta de pago
│   └── PaymentResult.php          # Resultado de operación
│
├── Enums/                          # Enumeraciones
│   ├── PaymentProvider.php        # stripe|redsys|paypal|cash
│   ├── PaymentMethod.php          # card|bizum|cash
│   ├── PaymentState.php           # pending|completed|failed|...
│   └── PaymentType.php            # api|redirect|alternative
│
├── Services/Payments/              # Servicios de pago
│   ├── PaymentGateway.php         # Interface común
│   ├── PaymentManager.php         # Factory (crea gateways)
│   ├── StripePaymentService.php   # Implementación Stripe
│   ├── RedsysPaymentService.php   # Implementación Redsys
│   └── PayPalPaymentService.php   # Implementación PayPal
│
└── Http/Controllers/
    └── PaymentController.php      # Controlador principal

config/
└── payments.php                    # Configuración de pagos

resources/views/
└── payments/
    ├── index.blade.php            # Página principal
    ├── stripe.blade.php           # Ejemplo Stripe
    ├── redsys.blade.php           # Ejemplo Redsys
    ├── paypal.blade.php           # Ejemplo PayPal
    ├── refund.blade.php           # Ejemplo reembolsos
    ├── success.blade.php          # Página de éxito
    ├── error.blade.php            # Página de error
    └── cancelled.blade.php        # Página de cancelación

routes/
└── web.php                        # Rutas de la aplicación
```

---

## 🎯 Uso Básico

### Desde un Controlador

```php
<?php

namespace App\Http\Controllers;

use App\DTOs\PaymentRequest;
use App\Enums\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Services\Payments\PaymentManager;

class CheckoutController extends Controller
{
    public function __construct(
        private PaymentManager $paymentManager
    ) {}
    
    public function processPayment()
    {
        // 1. Obtener el gateway del proveedor elegido
        $gateway = $this->paymentManager->driver(PaymentProvider::STRIPE);
        
        // 2. Crear solicitud de pago
        $request = new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'ORDER-' . time(),
            metadata: [
                'description' => 'Pedido #123',
                'customer_email' => 'cliente@example.com'
            ],
            returnUrl: route('payments.return'),
            cancelUrl: route('payments.cancel')
        );
        
        // 3. Iniciar pago
        $response = $gateway->initiate($request);
        
        // 4. Manejar respuesta según el tipo
        if ($response->isApi()) {
            // Stripe: Devolver clientSecret al frontend
            return response()->json([
                'clientSecret' => $response->clientSecret
            ]);
        }
        
        if ($response->isRedirect()) {
            // Redsys/PayPal: Mostrar formulario o redirigir
            if ($response->redirectUrl) {
                return redirect($response->redirectUrl);
            }
            
            return view('payment-form', [
                'formHtml' => $response->formHtml
            ]);
        }
    }
}
```

### Inyección de Dependencias

Laravel inyecta automáticamente el `PaymentManager`:

```php
use App\Services\Payments\PaymentManager;

class MyController extends Controller
{
    // Inyección en constructor
    public function __construct(
        private PaymentManager $paymentManager
    ) {}
    
    // O inyección en método
    public function process(PaymentManager $manager)
    {
        $gateway = $manager->driver(PaymentProvider::STRIPE);
        // ...
    }
}
```

---

## 🎯 Sistema de Eventos

### ¿Qué es?

El sistema de eventos permite ejecutar **acciones automáticas** cuando un pago se completa exitosamente, **independientemente del proveedor** utilizado (Stripe, Redsys, PayPal).

### Flujo

```
Pago Completado (Stripe/Redsys/PayPal)
    ↓
event(PaymentCompleted)
    ↓
Laravel ejecuta Listeners:
    → LogPaymentToDatabase    (Guarda en BD)
    → SendConfirmationEmail   (Email al cliente)
    → SendAdminNotification   (Notifica al admin)
    → UpdateInventory         (Actualiza stock)
```

### Ventajas

✅ **Sin duplicación** - Código común para todos los proveedores
✅ **Fácil extensión** - Añadir nuevas acciones sin tocar controladores
✅ **Desacoplado** - Lógica de negocio separada de lógica de pagos
✅ **Testeable** - Fácil de probar con `Event::fake()`
✅ **Asíncrono** - Listeners pueden ejecutarse en background (colas)

### Componentes

#### Evento: `PaymentCompleted`
**Ubicación:** `app/Events/PaymentCompleted.php`

Encapsula toda la información de un pago completado:

```php
event(new PaymentCompleted(
    provider: PaymentProvider::STRIPE,
    result: $paymentResult,
    orderId: 'ORDER-123',
    amount: 99.99,
    currency: 'EUR',
    metadata: ['user_id' => 42, 'items' => [...]],
    customerEmail: 'cliente@example.com'
));
```

#### Listeners
**Ubicación:** `app/Listeners/`

- **`LogPaymentToDatabase`** ⚡ Síncrono - Guarda el pago en BD
- **`SendPaymentConfirmationEmail`** 📧 Asíncrono - Email al cliente
- **`SendAdminNotification`** 📢 Asíncrono - Notifica al admin
- **`UpdateInventory`** 📦 Síncrono - Actualiza inventario/stock

#### Registro
**Ubicación:** `app/Providers/AppServiceProvider.php`

```php
Event::listen(PaymentCompleted::class, [
    LogPaymentToDatabase::class,
    SendPaymentConfirmationEmail::class,
    SendAdminNotification::class,
    UpdateInventory::class,
]);
```

### Uso en Controllers

```php
// Cuando un pago se completa exitosamente
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
```

### Añadir Nuevas Acciones

1. **Crear Listener:**
```bash
php artisan make:listener NombreListener --event=PaymentCompleted
```

2. **Implementar:**
```php
class NombreListener
{
    public function handle(PaymentCompleted $event): void
    {
        // Tu lógica aquí
    }
}
```

3. **Registrar en `AppServiceProvider`:**
```php
Event::listen(PaymentCompleted::class, [
    // ... existentes
    NombreListener::class,
]);
```

### Documentación Completa

📚 **[EVENTOS_LARAVEL.md](EVENTOS_LARAVEL.md)** - Guía completa del sistema de eventos

---

## 🔧 Configuración

### Archivo `config/payments.php`

```php
return [
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    
    'redsys' => [
        'merchant_code' => env('REDSYS_MERCHANT_CODE'),
        'secret_key' => env('REDSYS_SECRET_KEY'),
        'terminal' => env('REDSYS_TERMINAL', '1'),
        'environment' => env('REDSYS_ENVIRONMENT', 'test'),
    ],
    
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'),
    ],
];
```

### Acceso a Configuración

```php
// Desde cualquier parte del código
$stripeKey = config('payments.stripe.secret_key');
$redsysEnv = config('payments.redsys.environment');
```

---

## 🎨 Vistas Blade

### Página Principal

```
GET / → payments.index
```

Muestra las opciones de pago disponibles.

### Ejemplos de Pago

```
GET /payments/stripe   → payments.stripe.example
GET /payments/redsys   → payments.redsys.example
GET /payments/paypal   → payments.paypal.example
GET /payments/refund   → payments.refund.example
```

### Callbacks

```
POST /payments/stripe/initiate   → payments.stripe.initiate
POST /payments/redsys/initiate   → payments.redsys.initiate
ANY  /payments/redsys/return     → payments.redsys.return
GET  /payments/paypal/return     → payments.paypal.return
```

---

## 🧪 Testing

### Tests Básicos

```php
<?php

namespace Tests\Feature;

use App\DTOs\PaymentRequest;
use App\Enums\PaymentProvider;
use App\Services\Payments\PaymentManager;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    public function test_stripe_payment_initiation()
    {
        $manager = app(PaymentManager::class);
        $gateway = $manager->driver(PaymentProvider::STRIPE);
        
        $request = new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'TEST-123'
        );
        
        $response = $gateway->initiate($request);
        
        $this->assertTrue($response->isApi());
        $this->assertNotNull($response->clientSecret);
    }
    
    public function test_payment_manager_caches_instances()
    {
        $manager = app(PaymentManager::class);
        
        $gateway1 = $manager->driver(PaymentProvider::STRIPE);
        $gateway2 = $manager->driver(PaymentProvider::STRIPE);
        
        $this->assertSame($gateway1, $gateway2);
    }
}
```

---

## 🔐 Seguridad

### ✅ Implementado

- ✅ Variables de entorno con `.env`
- ✅ Configuración centralizada en `config/payments.php`
- ✅ Validación de firmas en Redsys
- ✅ CSRF protection en formularios
- ✅ Validación estricta de credenciales

### ⚠️ Antes de Producción

1. **Cambiar a claves de producción:**
   ```env
   STRIPE_SECRET_KEY=sk_live_...
   REDSYS_ENVIRONMENT=live
   PAYPAL_ENVIRONMENT=live
   ```

2. **Activar HTTPS:**
   - Configurar certificado SSL
   - Forzar HTTPS en Laravel: `URL::forceScheme('https')`

3. **Configurar Webhooks:**
   - Stripe: Dashboard → Webhooks
   - PayPal: Developer Dashboard → Webhooks
   - Redsys: Notificar URL de callback al banco

4. **Rate Limiting:**
   ```php
   Route::middleware(['throttle:60,1'])->group(function () {
       // Rutas de pago
   });
   ```

5. **Logging:**
   ```php
   Log::channel('payments')->info('Payment initiated', [
       'provider' => 'stripe',
       'amount' => 50.00,
   ]);
   ```

---

## 🚀 Diferencias con PHP Vanilla

| Característica | PHP Vanilla | Laravel |
|---|---|---|
| **Autoloading** | Composer | PSR-4 + Laravel |
| **Configuración** | `.env` + helper | `config()` helper |
| **Rutas** | Manual | `routes/web.php` |
| **Vistas** | PHP puro | Blade templates |
| **Dependency Injection** | Manual | Automático |
| **CSRF Protection** | Manual | `@csrf` |
| **Sesiones** | `$_SESSION` | `session()` |
| **Request** | `$_POST`, `$_GET` | `Request $request` |
| **Response** | `header()`, `echo` | `response()`, `view()` |

---

## 📚 Próximos Pasos

### Event System (Recomendado)

Para gestionar acciones post-pago (enviar email, generar factura, etc.), consulta:

- `README_EVENT_SYSTEM.md` en la carpeta padre
- Implementación con eventos de Laravel
- Listeners síncronos y asíncronos

### Integración con Base de Datos

```php
// app/Models/Payment.php
class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'amount',
        'currency',
        'status',
        'transaction_id',
    ];
    
    protected $casts = [
        'provider' => PaymentProvider::class,
        'status' => PaymentState::class,
    ];
}
```

```php
// Guardar pago después de confirmar
$payment = Payment::create([
    'order_id' => $request->orderId,
    'provider' => PaymentProvider::STRIPE,
    'amount' => $request->amount,
    'currency' => $request->currency,
    'status' => PaymentState::COMPLETED,
    'transaction_id' => $result->transactionId,
]);
```

---

## 🆘 Problemas Comunes

### PayPal Deprecation Warnings

Si ves warnings con PHP 8.2+, es un problema conocido del SDK de PayPal.

**Solución temporal:** Añadir al inicio de los controladores:
```php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
```

Ver más en: `../PROBLEMAS_CONOCIDOS.md`

### Redsys Signature Mismatch

Si la firma de Redsys no coincide:
- Verifica que la clave secreta sea correcta
- Asegúrate de que el entorno sea `test` o `live` consistentemente
- Revisa que el `terminal` sea el correcto

### Stripe Webhook Errors

Si los webhooks de Stripe fallan:
- Verifica que `STRIPE_WEBHOOK_SECRET` esté configurado
- Comprueba que la URL del webhook sea accesible públicamente
- Usa ngrok para testing local: `ngrok http 8000`

---

## 📞 Recursos

### Documentación

- [Laravel](https://laravel.com/docs)
- [Stripe PHP SDK](https://stripe.com/docs/api/php)
- [Redsys/Sermepa](https://github.com/ssheduardo/sermepa-tpv)
- [PayPal Checkout SDK](https://github.com/paypal/Checkout-PHP-SDK)

### Ejemplos

- Todos los ejemplos están en `/resources/views/payments/`
- Cada vista incluye comentarios explicativos
- El controlador tiene ejemplos de uso real

---

**🎉 ¡Disfruta del sistema de pagos en Laravel!**

Para más información sobre la arquitectura y patrones de diseño, consulta los archivos README en la carpeta padre (`../`).

