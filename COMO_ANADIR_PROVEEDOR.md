# 🚀 Cómo Añadir un Nuevo Proveedor de Pago

Esta guía te explica cómo añadir tu propio proveedor de pago personalizado al sistema, sin necesidad de modificar el código del paquete.

---

## 📋 Requisitos Previos

- Tu proveedor debe tener un SDK o API REST disponible
- Conocimiento básico de PHP y Laravel
- Entender la interfaz `PaymentGateway`

---

## 🎯 Paso 1: Crear el Servicio del Proveedor

Crea una nueva clase que implemente la interfaz `PaymentGateway`:

```php
<?php

namespace App\Services\Payments;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\PaymentResult;
use App\Enums\PaymentType;
use App\Exceptions\PaymentConfigurationException;
use App\Exceptions\PaymentProviderException;

class MercadoPagoPaymentService implements PaymentGateway
{
    private string $accessToken;
    private string $environment;

    public function __construct(
        ?string $accessToken = null,
        ?string $environment = null
    ) {
        $this->accessToken = $accessToken ?? config('payments.mercadopago.access_token');
        $this->environment = $environment ?? config('payments.mercadopago.environment', 'sandbox');

        if (! $this->accessToken) {
            throw PaymentConfigurationException::missingCredentials('MercadoPago', 'access_token');
        }
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        // Tu lógica para iniciar el pago
        // Ejemplo con MercadoPago:
        
        $response = $this->makeApiCall('POST', '/v1/payments', [
            'transaction_amount' => $request->amount,
            'currency_id' => $request->currency,
            'description' => $request->metadata['description'] ?? 'Order ' . $request->orderId,
            'external_reference' => $request->orderId,
        ]);

        return new PaymentResponse(
            type: PaymentType::REDIRECT,
            data: [
                'payment_id' => $response['id'],
                'status' => $response['status'],
            ],
            redirectUrl: $response['init_point'] // URL de redirección
        );
    }

    public function capture(string $paymentId): PaymentResult
    {
        $response = $this->makeApiCall('GET', "/v1/payments/{$paymentId}");

        $success = $response['status'] === 'approved';

        return new PaymentResult(
            success: $success,
            status: $response['status'],
            paymentId: $paymentId,
            transactionId: $response['transaction_details']['transaction_id'] ?? null,
            message: $success ? 'Payment captured successfully' : 'Payment not approved',
            data: $response
        );
    }

    public function refund(string $paymentId, ?float $amount = null): PaymentResult
    {
        $data = ['payment_id' => $paymentId];
        
        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        $response = $this->makeApiCall('POST', '/v1/payments/' . $paymentId . '/refunds', $data);

        return new PaymentResult(
            success: $response['status'] === 'approved',
            status: 'refunded',
            transactionId: $response['id'] ?? null,
            message: 'Refund processed successfully'
        );
    }

    public function getStatus(string $paymentId): PaymentResult
    {
        $response = $this->makeApiCall('GET', "/v1/payments/{$paymentId}");

        return new PaymentResult(
            success: $response['status'] === 'approved',
            status: $response['status'],
            paymentId: $paymentId,
            transactionId: $response['transaction_details']['transaction_id'] ?? null,
            data: $response
        );
    }

    public function verifyCallback(array $postData): PaymentResult
    {
        // Verificar la firma/autenticidad del callback
        if (! $this->verifySignature($postData)) {
            throw PaymentProviderException::signatureVerificationFailed(
                \App\Enums\PaymentProvider::from('mercadopago')
            );
        }

        $paymentId = $postData['data']['id'] ?? null;

        if (! $paymentId) {
            throw PaymentProviderException::invalidResponse(
                \App\Enums\PaymentProvider::from('mercadopago'),
                'Missing payment ID in callback'
            );
        }

        return $this->capture($paymentId);
    }

    /**
     * Método helper para hacer llamadas a la API
     */
    private function makeApiCall(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->environment === 'production'
            ? 'https://api.mercadopago.com'
            : 'https://api.mercadopago.com';

        $ch = curl_init($url . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $method !== 'GET' ? json_encode($data) : null,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw PaymentProviderException::apiError(
                \App\Enums\PaymentProvider::from('mercadopago'),
                "API Error: HTTP {$httpCode}",
                (string) $httpCode
            );
        }

        return json_decode($response, true);
    }

    /**
     * Verificar firma del callback
     */
    private function verifySignature(array $postData): bool
    {
        // Implementar verificación de firma según la documentación de MercadoPago
        // Por ejemplo, verificar X-Signature header
        return true; // Simplificado
    }
}
```

---

## 📝 Paso 2: Implementar Todos los Métodos Requeridos

La interfaz `PaymentGateway` requiere estos métodos:

### `initiate(PaymentRequest $request): PaymentResponse`

Inicia un nuevo pago. Debe retornar:
- `PaymentType::API` si requiere integración con JavaScript (como Stripe)
- `PaymentType::REDIRECT` si redirige al usuario (como PayPal, Redsys)
- `PaymentType::ALTERNATIVE` para métodos alternativos (QR, etc.)

**Ejemplo:**
```php
public function initiate(PaymentRequest $request): PaymentResponse
{
    // Crear pago en el proveedor
    $payment = $this->createPayment($request);
    
    return new PaymentResponse(
        type: PaymentType::REDIRECT,
        data: ['payment_id' => $payment['id']],
        redirectUrl: $payment['checkout_url']
    );
}
```

### `capture(string $paymentId): PaymentResult`

Captura/confirma un pago. Retorna el estado final.

**Ejemplo:**
```php
public function capture(string $paymentId): PaymentResult
{
    $payment = $this->getPayment($paymentId);
    
    return new PaymentResult(
        success: $payment['status'] === 'completed',
        status: $payment['status'],
        paymentId: $paymentId,
        transactionId: $payment['transaction_id'],
        message: 'Payment captured'
    );
}
```

### `refund(string $paymentId, ?float $amount = null): PaymentResult`

Procesa un reembolso. Si `$amount` es `null`, reembolsa el total.

**Ejemplo:**
```php
public function refund(string $paymentId, ?float $amount = null): PaymentResult
{
    $refund = $this->processRefund($paymentId, $amount);
    
    return new PaymentResult(
        success: $refund['status'] === 'refunded',
        status: 'refunded',
        transactionId: $refund['id'],
        message: 'Refund processed successfully'
    );
}
```

### `getStatus(string $paymentId): PaymentResult`

Obtiene el estado actual de un pago.

**Ejemplo:**
```php
public function getStatus(string $paymentId): PaymentResult
{
    $payment = $this->getPayment($paymentId);
    
    return new PaymentResult(
        success: $payment['status'] === 'completed',
        status: $payment['status'],
        paymentId: $paymentId,
        data: $payment
    );
}
```

### `verifyCallback(array $postData): PaymentResult`

Verifica y procesa un callback/webhook del proveedor.

**Ejemplo:**
```php
public function verifyCallback(array $postData): PaymentResult
{
    // 1. Verificar firma/autenticidad
    if (! $this->verifySignature($postData)) {
        throw PaymentProviderException::signatureVerificationFailed(...);
    }
    
    // 2. Extraer datos del pago
    $paymentId = $postData['payment_id'];
    
    // 3. Obtener estado actualizado
    return $this->capture($paymentId);
}
```

---

## ⚙️ Paso 3: Configurar Credenciales

Añade la configuración en `config/payments.php`:

```php
'mercadopago' => [
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    'environment' => env('MERCADOPAGO_ENVIRONMENT', 'sandbox'), // sandbox o production
],
```

Y en tu `.env`:

```env
MERCADOPAGO_ACCESS_TOKEN=tu_access_token_aqui
MERCADOPAGO_ENVIRONMENT=sandbox
```

---

## 🔧 Paso 4: Registrar el Driver

En `app/Providers/AppServiceProvider.php`:

```php
use App\Facades\Payment;
use App\Services\Payments\MercadoPagoPaymentService;

public function boot(): void
{
    // Registrar MercadoPago
    Payment::extend('mercadopago', function ($manager) {
        return new MercadoPagoPaymentService(
            accessToken: config('payments.mercadopago.access_token'),
            environment: config('payments.mercadopago.environment')
        );
    });
}
```

---

## ✅ Paso 5: Usar el Nuevo Proveedor

Ahora puedes usar tu proveedor personalizado en cualquier lugar:

```php
use App\Facades\Payment;
use App\DTOs\PaymentRequest;

// Obtener el driver
$gateway = Payment::driver('mercadopago');

// Iniciar un pago
$request = new PaymentRequest(
    amount: 50.00,
    currency: 'EUR',
    orderId: 'ORDER-123'
);

$response = $gateway->initiate($request);

// Redirigir al usuario si es necesario
if ($response->isRedirect()) {
    return redirect($response->redirectUrl);
}
```

---

## 🎨 Ejemplo Completo: Square Payment

```php
<?php

namespace App\Services\Payments;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\PaymentResult;
use App\Enums\PaymentType;
use App\Exceptions\PaymentConfigurationException;
use App\Exceptions\PaymentProviderException;
use App\Enums\PaymentProvider;

class SquarePaymentService implements PaymentGateway
{
    private string $accessToken;
    private string $locationId;
    private string $environment;

    public function __construct(
        ?string $accessToken = null,
        ?string $locationId = null,
        ?string $environment = null
    ) {
        $this->accessToken = $accessToken ?? config('payments.square.access_token');
        $this->locationId = $locationId ?? config('payments.square.location_id');
        $this->environment = $environment ?? config('payments.square.environment', 'sandbox');

        if (! $this->accessToken) {
            throw PaymentConfigurationException::missingCredentials('Square', 'access_token');
        }
        if (! $this->locationId) {
            throw PaymentConfigurationException::missingCredentials('Square', 'location_id');
        }
    }

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        $apiUrl = $this->environment === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';

        $response = $this->makeApiCall('POST', $apiUrl . '/v2/payments', [
            'source_id' => 'cnon:card-nonce-ok', // En producción, esto viene del frontend
            'idempotency_key' => uniqid(),
            'amount_money' => [
                'amount' => (int) ($request->amount * 100),
                'currency' => $request->currency,
            ],
            'reference_id' => $request->orderId,
        ]);

        return new PaymentResponse(
            type: PaymentType::API,
            data: [
                'payment_id' => $response['payment']['id'],
                'status' => $response['payment']['status'],
            ],
            clientSecret: $response['payment']['id'] // Square usa el ID como client secret
        );
    }

    public function capture(string $paymentId): PaymentResult
    {
        $apiUrl = $this->environment === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';

        $response = $this->makeApiCall('GET', $apiUrl . "/v2/payments/{$paymentId}");

        $payment = $response['payment'];
        $success = $payment['status'] === 'COMPLETED';

        return new PaymentResult(
            success: $success,
            status: strtolower($payment['status']),
            paymentId: $paymentId,
            transactionId: $payment['id'],
            message: $success ? 'Payment captured successfully' : 'Payment not completed',
            data: $payment
        );
    }

    public function refund(string $paymentId, ?float $amount = null): PaymentResult
    {
        $apiUrl = $this->environment === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';

        $data = [
            'idempotency_key' => uniqid(),
            'payment_id' => $paymentId,
        ];

        if ($amount !== null) {
            $data['amount_money'] = [
                'amount' => (int) ($amount * 100),
                'currency' => 'EUR',
            ];
        }

        $response = $this->makeApiCall('POST', $apiUrl . '/v2/refunds', $data);

        return new PaymentResult(
            success: $response['refund']['status'] === 'COMPLETED',
            status: 'refunded',
            transactionId: $response['refund']['id'],
            message: 'Refund processed successfully'
        );
    }

    public function getStatus(string $paymentId): PaymentResult
    {
        return $this->capture($paymentId);
    }

    public function verifyCallback(array $postData): PaymentResult
    {
        // Square usa webhooks, no callbacks tradicionales
        // Implementar según documentación de Square
        throw PaymentProviderException::invalidResponse(
            PaymentProvider::from('square'),
            'Square uses webhooks instead of callbacks'
        );
    }

    private function makeApiCall(string $method, string $url, array $data = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'Square-Version: 2023-10-18',
            ],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $method !== 'GET' ? json_encode($data) : null,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw PaymentProviderException::apiError(
                PaymentProvider::from('square'),
                "API Error: HTTP {$httpCode}",
                (string) $httpCode
            );
        }

        return json_decode($response, true);
    }
}
```

**Registro:**
```php
Payment::extend('square', function ($manager) {
    return new SquarePaymentService(
        accessToken: config('payments.square.access_token'),
        locationId: config('payments.square.location_id'),
        environment: config('payments.square.environment')
    );
});
```

---

## 🎯 Mejores Prácticas

### 1. Usar Excepciones del Paquete

```php
// ✅ Correcto
throw PaymentConfigurationException::missingCredentials('MercadoPago', 'access_token');
throw PaymentProviderException::apiError(...);

// ❌ Incorrecto
throw new \Exception('Missing credentials');
```

### 2. Validar Configuración en el Constructor

```php
public function __construct(...)
{
    if (! $this->accessToken) {
        throw PaymentConfigurationException::missingCredentials('Provider', 'access_token');
    }
}
```

### 3. Usar el Trait LogsPayments

```php
use App\Concerns\LogsPayments;

class MercadoPagoPaymentService implements PaymentGateway
{
    use LogsPayments;

    public function initiate(PaymentRequest $request): PaymentResponse
    {
        $this->logPaymentAttempt(PaymentProvider::from('mercadopago'), $request);
        
        // ... tu código ...
        
        $this->logPaymentInitiated(...);
    }
}
```

### 4. Manejar Errores de API

```php
try {
    $response = $this->makeApiCall(...);
} catch (\Exception $e) {
    $this->logPaymentError(PaymentProvider::from('mercadopago'), $e);
    throw PaymentProviderException::apiError(...);
}
```

### 5. Validar Respuestas

```php
if (! isset($response['payment_id'])) {
    throw PaymentProviderException::invalidResponse(
        PaymentProvider::from('mercadopago'),
        'Missing payment_id in response'
    );
}
```

---

## 🧪 Testing

Crea tests para tu driver personalizado:

```php
use Tests\TestCase;
use App\Services\Payments\MercadoPagoPaymentService;
use App\DTOs\PaymentRequest;

class MercadoPagoPaymentServiceTest extends TestCase
{
    public function test_it_creates_payment()
    {
        $service = new MercadoPagoPaymentService(
            accessToken: 'test_token',
            environment: 'sandbox'
        );

        $request = new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            orderId: 'TEST-123'
        );

        $response = $service->initiate($request);

        $this->assertNotNull($response->redirectUrl);
        $this->assertEquals('mercadopago', $response->data['provider']);
    }
}
```

---

## 📚 Recursos

- [Interfaz PaymentGateway](app/Services/Payments/PaymentGateway.php)
- [DTOs disponibles](app/DTOs/)
- [Excepciones del sistema](app/Exceptions/)
- [Ejemplo: StripePaymentService](app/Services/Payments/StripePaymentService.php)
- [Ejemplo: PayPalPaymentService](app/Services/Payments/PayPalPaymentService.php)

---

## ❓ Preguntas Frecuentes

### ¿Puedo sobrescribir un driver del paquete?

Sí, si registras un driver con el mismo nombre que uno del paquete, tu driver personalizado tendrá prioridad.

```php
// Esto sobrescribe el driver Stripe del paquete
Payment::extend('stripe', function ($manager) {
    return new MiStripeCustomizado();
});
```

### ¿Necesito implementar todos los métodos?

Sí, todos los métodos de `PaymentGateway` son requeridos. Si tu proveedor no soporta alguna funcionalidad, lanza una excepción apropiada:

```php
public function refund(string $paymentId, ?float $amount = null): PaymentResult
{
    throw PaymentProviderException::refundNotAvailable(
        PaymentProvider::from('mi_proveedor'),
        'Refunds are not supported by this provider'
    );
}
```

### ¿Cómo manejo webhooks en lugar de callbacks?

Implementa `verifyCallback()` para procesar webhooks. Verifica la firma y procesa el evento:

```php
public function verifyCallback(array $postData): PaymentResult
{
    // Verificar firma del webhook
    if (! $this->verifyWebhookSignature($postData)) {
        throw PaymentProviderException::signatureVerificationFailed(...);
    }
    
    // Procesar evento
    $event = $postData['event'];
    
    if ($event['type'] === 'payment.completed') {
        return $this->capture($event['data']['id']);
    }
    
    // Otros tipos de eventos...
}
```

---

**¡Listo! Ahora puedes añadir cualquier proveedor de pago que necesites.** 🎉

