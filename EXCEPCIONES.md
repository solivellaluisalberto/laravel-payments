# 🚨 Sistema de Excepciones Personalizadas

## Introducción

El sistema de pagos utiliza un conjunto de excepciones personalizadas para proporcionar mensajes de error claros, contexto detallado y códigos de error consistentes. Esto facilita el debugging, el logging y el manejo de errores en producción.

## Jerarquía de Excepciones

```
PaymentException (base)
├── PaymentConfigurationException
├── PaymentProviderException
├── PaymentValidationException
└── InvalidPaymentStateException
```

---

## 1. PaymentException (Base)

**Descripción:** Excepción base para todos los errores relacionados con pagos.

**Características:**
- ✅ Almacena contexto adicional
- ✅ Método `toArray()` para serialización
- ✅ Método `render()` para respuestas HTTP
- ✅ Códigos de estado HTTP apropiados

**Ejemplo:**
```php
try {
    // código que puede fallar
} catch (PaymentException $e) {
    // Acceder al contexto
    $context = $e->getContext();
    
    // Convertir a array
    $errorData = $e->toArray();
    
    // Retornar respuesta HTTP automática
    return $e->render();
}
```

---

## 2. PaymentConfigurationException

**Descripción:** Errores de configuración (credenciales faltantes, configuración inválida).

**Código HTTP:** `500` (Internal Server Error)

### Métodos Estáticos

#### `missingCredentials()`
```php
throw PaymentConfigurationException::missingCredentials('Stripe', 'secret_key');
```

**Contexto incluido:**
```php
[
    'provider' => 'Stripe',
    'credential' => 'secret_key',
    'config_key' => 'payments.stripe.secret_key'
]
```

#### `invalidApiKey()`
```php
throw PaymentConfigurationException::invalidApiKey('PayPal');
```

#### `invalidEnvironment()`
```php
throw PaymentConfigurationException::invalidEnvironment('Redsys', 'invalid_env');
```

#### `unsupportedProvider()`
```php
throw PaymentConfigurationException::unsupportedProvider('CustomProvider');
```

#### `invalidConfiguration()`
```php
throw PaymentConfigurationException::invalidConfiguration('Stripe', 'Webhook URL is required');
```

---

## 3. PaymentProviderException

**Descripción:** Errores en la comunicación con proveedores de pago (APIs, timeouts, rechazos).

**Código HTTP:** `502` (Bad Gateway) o `404`/`402` según el caso

### Métodos Estáticos

#### `apiError()`
```php
throw PaymentProviderException::apiError(
    PaymentProvider::STRIPE,
    'Card declined',
    'card_declined',
    $originalException
);
```

**Contexto incluido:**
```php
[
    'provider' => 'stripe',
    'provider_error_code' => 'card_declined'
]
```

#### `connectionError()`
```php
throw PaymentProviderException::connectionError(PaymentProvider::PAYPAL);
```

#### `timeout()`
```php
throw PaymentProviderException::timeout(PaymentProvider::REDSYS);
```

#### `invalidResponse()`
```php
throw PaymentProviderException::invalidResponse(
    PaymentProvider::STRIPE,
    'Missing required fields'
);
```

#### `paymentDeclined()`
```php
throw PaymentProviderException::paymentDeclined(
    PaymentProvider::REDSYS,
    'Insufficient funds',
    '180'
);
```

#### `signatureVerificationFailed()`
```php
throw PaymentProviderException::signatureVerificationFailed(PaymentProvider::REDSYS);
```

#### `paymentNotFound()`
```php
throw PaymentProviderException::paymentNotFound(
    PaymentProvider::STRIPE,
    'pi_123456789'
);
```

#### `refundNotAvailable()`
```php
throw PaymentProviderException::refundNotAvailable(
    PaymentProvider::PAYPAL,
    'Payment not captured yet'
);
```

---

## 4. PaymentValidationException

**Descripción:** Errores de validación de datos de entrada.

**Código HTTP:** `422` (Unprocessable Entity)

### Métodos Estáticos

#### `invalidAmount()`
```php
throw PaymentValidationException::invalidAmount(-50.00, 'Amount must be positive');
```

**Contexto incluido:**
```php
[
    'amount' => -50.00,
    'reason' => 'Amount must be positive'
]
```

#### `invalidCurrency()`
```php
throw PaymentValidationException::invalidCurrency('US');
```

#### `invalidOrderId()`
```php
throw PaymentValidationException::invalidOrderId('', 'Order ID cannot be empty');
```

#### `invalidReturnUrl()`
```php
throw PaymentValidationException::invalidReturnUrl(null);
```

#### `unsupportedPaymentMethod()`
```php
throw PaymentValidationException::unsupportedPaymentMethod('bitcoin', 'Stripe');
```

#### `missingRequiredField()`
```php
throw PaymentValidationException::missingRequiredField('customer_email');
```

#### `invalidEmail()`
```php
throw PaymentValidationException::invalidEmail('invalid-email');
```

#### `invalidFieldLength()`
```php
throw PaymentValidationException::invalidFieldLength('description', 256, 100);
```

#### `validationFailed()`
```php
throw PaymentValidationException::validationFailed('amount', 'Must be between 0.50 and 999999');
```

---

## 5. InvalidPaymentStateException

**Descripción:** Errores de estado de pago (transiciones inválidas, operaciones no permitidas).

**Código HTTP:** `409` (Conflict)

### Métodos Estáticos

#### `cannotCapture()`
```php
throw InvalidPaymentStateException::cannotCapture('pi_123', 'failed');
```

#### `cannotRefund()`
```php
throw InvalidPaymentStateException::cannotRefund('pi_123', 'pending');
```

#### `cannotCancel()`
```php
throw InvalidPaymentStateException::cannotCancel('pi_123', 'completed');
```

#### `alreadyProcessed()`
```php
throw InvalidPaymentStateException::alreadyProcessed('pi_123');
```

#### `expired()`
```php
throw InvalidPaymentStateException::expired('pi_123');
```

#### `invalidStateTransition()`
```php
throw InvalidPaymentStateException::invalidStateTransition('pi_123', 'pending', 'refunded');
```

#### `alreadyRefunded()`
```php
throw InvalidPaymentStateException::alreadyRefunded('pi_123');
```

#### `invalidRefundAmount()`
```php
throw InvalidPaymentStateException::invalidRefundAmount('pi_123', 100.00, 50.00);
```

---

## Códigos de Error

### Rango 1000-1999: Configuración
- `1001`: Credenciales faltantes
- `1002`: API key inválida
- `1003`: Entorno inválido
- `1004`: Proveedor no soportado
- `1005`: Configuración inválida

### Rango 2000-2999: Proveedor
- `2001`: Error de API
- `2002`: Error de conexión
- `2003`: Timeout
- `2004`: Respuesta inválida
- `2005`: Pago rechazado
- `2006`: Verificación de firma fallida
- `2007`: Pago no encontrado
- `2008`: Reembolso no disponible

### Rango 3000-3999: Validación
- `3001`: Monto inválido
- `3002`: Moneda inválida
- `3003`: Order ID inválido
- `3004`: URL de retorno inválida
- `3005`: Método de pago no soportado
- `3006`: Campo requerido faltante
- `3007`: Email inválido
- `3008`: Longitud de campo inválida
- `3009`: Validación fallida

### Rango 4000-4999: Estado
- `4001`: No se puede capturar
- `4002`: No se puede reembolsar
- `4003`: No se puede cancelar
- `4004`: Ya procesado
- `4005`: Expirado
- `4006`: Transición de estado inválida
- `4007`: Ya reembolsado
- `4008`: Monto de reembolso inválido

---

## Manejo de Excepciones en Controladores

### Opción 1: Try-Catch Manual

```php
public function stripeInitiate(Request $request)
{
    try {
        $gateway = $this->paymentManager->driver(PaymentProvider::STRIPE);
        
        $paymentRequest = new PaymentRequest(
            amount: $request->input('amount'),
            currency: 'EUR',
            orderId: 'ORDER-'.time()
        );
        
        $response = $gateway->initiate($paymentRequest);
        
        return response()->json([
            'success' => true,
            'data' => $response,
        ]);
        
    } catch (PaymentConfigurationException $e) {
        // Error de configuración - logging crítico
        Log::critical('Payment configuration error', [
            'error' => $e->toArray(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Service temporarily unavailable',
        ], 503);
        
    } catch (PaymentValidationException $e) {
        // Error de validación - retornar al usuario
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => $e->getContext(),
        ], 422);
        
    } catch (PaymentProviderException $e) {
        // Error del proveedor - logging y mensaje genérico
        Log::error('Payment provider error', [
            'error' => $e->toArray(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Payment processing failed. Please try again.',
        ], 502);
        
    } catch (PaymentException $e) {
        // Cualquier otra excepción de pago
        Log::error('Payment error', [
            'error' => $e->toArray(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $e->getHttpStatusCode());
    }
}
```

### Opción 2: Handler Global

Puedes registrar un handler global en `app/Exceptions/Handler.php`:

```php
use App\Exceptions\PaymentException;
use App\Exceptions\PaymentConfigurationException;
use App\Exceptions\PaymentValidationException;

public function register(): void
{
    $this->renderable(function (PaymentConfigurationException $e) {
        Log::critical('Payment configuration error', $e->toArray());
        
        return response()->json([
            'error' => 'Service temporarily unavailable',
            'message' => config('app.debug') ? $e->getMessage() : 'Configuration error',
        ], 503);
    });
    
    $this->renderable(function (PaymentValidationException $e) {
        return response()->json([
            'error' => 'Validation error',
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
        ], 422);
    });
    
    $this->renderable(function (PaymentException $e) {
        Log::error('Payment error', $e->toArray());
        
        return $e->render();
    });
}
```

---

## Logging y Monitoring

### Configuración de Canal de Logging

```php
// config/logging.php
'channels' => [
    'payments' => [
        'driver' => 'daily',
        'path' => storage_path('logs/payments.log'),
        'level' => 'debug',
        'days' => 30,
    ],
],
```

### Uso en Servicios

```php
use Illuminate\Support\Facades\Log;

try {
    // Operación de pago
} catch (PaymentException $e) {
    Log::channel('payments')->error('Payment failed', [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'context' => $e->getContext(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    throw $e;
}
```

---

## Testing con Excepciones

```php
use App\Exceptions\PaymentConfigurationException;
use App\Exceptions\PaymentProviderException;
use App\Enums\PaymentProvider;

class PaymentServiceTest extends TestCase
{
    /** @test */
    public function it_throws_exception_when_credentials_missing()
    {
        config(['payments.stripe.secret_key' => null]);
        
        $this->expectException(PaymentConfigurationException::class);
        $this->expectExceptionCode(1001);
        
        new StripePaymentService();
    }
    
    /** @test */
    public function it_throws_exception_when_payment_not_found()
    {
        $this->expectException(PaymentProviderException::class);
        $this->expectExceptionCode(2007);
        
        $service = new StripePaymentService();
        $service->capture('pi_invalid_id');
    }
    
    /** @test */
    public function exception_includes_context()
    {
        try {
            throw PaymentConfigurationException::missingCredentials('Stripe', 'secret_key');
        } catch (PaymentConfigurationException $e) {
            $this->assertEquals('Stripe', $e->getContext()['provider']);
            $this->assertEquals('secret_key', $e->getContext()['credential']);
        }
    }
}
```

---

## Mejores Prácticas

### ✅ DO

1. **Usa excepciones específicas** en lugar de genéricas
```php
// ✅ Bueno
throw PaymentConfigurationException::missingCredentials('Stripe', 'api_key');

// ❌ Malo
throw new \Exception('Stripe API key not configured');
```

2. **Proporciona contexto** útil
```php
throw PaymentProviderException::paymentDeclined(
    PaymentProvider::REDSYS,
    'Insufficient funds',
    '180' // código del banco
);
```

3. **Registra excepciones** apropiadamente
```php
catch (PaymentConfigurationException $e) {
    Log::critical('Config error', $e->toArray());
}
```

4. **Retorna mensajes** amigables al usuario
```php
catch (PaymentProviderException $e) {
    return response()->json([
        'message' => 'Payment processing failed. Please try again.',
    ], 502);
}
```

### ❌ DON'T

1. **No captures excepciones** sin procesarlas
```php
// ❌ Malo
catch (PaymentException $e) {
    // silenciar error
}
```

2. **No expongas detalles** sensibles al frontend
```php
// ❌ Malo
return response()->json([
    'message' => $e->getMessage(), // puede contener info sensible
]);
```

3. **No ignores el contexto** de las excepciones
```php
// ❌ Malo
catch (PaymentException $e) {
    Log::error($e->getMessage()); // perdemos el contexto
}

// ✅ Bueno
catch (PaymentException $e) {
    Log::error('Payment error', $e->toArray());
}
```

---

## Beneficios del Sistema

✅ **Errores claros y consistentes**
✅ **Códigos de error únicos** para cada tipo de problema
✅ **Contexto detallado** para debugging
✅ **Códigos HTTP apropiados** automáticamente
✅ **Testing simplificado** con excepciones específicas
✅ **Logging estructurado** con toda la información
✅ **Separación de responsabilidades** (configuración, proveedor, validación, estado)

---

## Para el Paquete Laravel

Cuando conviertas esto en un paquete, estas excepciones:

1. ✅ Se pueden usar tal cual
2. ✅ Son independientes del framework
3. ✅ Tienen mensajes en inglés (internacionalizables)
4. ✅ Proporcionan una API consistente
5. ✅ Son extensibles para nuevos proveedores

---

**🎉 ¡Sistema de excepciones listo para producción!**

