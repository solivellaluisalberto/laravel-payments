# 🧪 Testing del Sistema de Excepciones

## Pruebas Rápidas para Verificar la Implementación

### 1️⃣ Test de Configuración Faltante

```bash
# En tu terminal Laravel
php artisan tinker
```

```php
// Simular credenciales faltantes
config(['payments.stripe.secret_key' => null]);

try {
    $service = new App\Services\Payments\StripePaymentService();
} catch (App\Exceptions\PaymentConfigurationException $e) {
    echo "✅ Excepción capturada correctamente\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    print_r($e->getContext());
}
```

**Resultado esperado:**
```
✅ Excepción capturada correctamente
Mensaje: Missing secret_key for Stripe. Please configure it in your .env file or config/payments.php
Código: 1001
Array
(
    [provider] => Stripe
    [credential] => secret_key
    [config_key] => payments.stripe.secret_key
)
```

---

### 2️⃣ Test de Pago No Encontrado

```php
use App\Services\Payments\StripePaymentService;
use App\Exceptions\PaymentProviderException;

try {
    $service = new StripePaymentService();
    $result = $service->capture('pi_invalid_payment_id_123');
} catch (PaymentProviderException $e) {
    echo "✅ Excepción de proveedor capturada\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "HTTP Status: " . $e->getHttpStatusCode() . "\n";
}
```

**Resultado esperado:**
```
✅ Excepción de proveedor capturada
Mensaje: Payment 'pi_invalid_payment_id_123' not found in Stripe.
Código: 2007
HTTP Status: 404
```

---

### 3️⃣ Test de Validación (Próxima implementación)

Cuando implementes la validación en DTOs:

```php
use App\DTOs\PaymentRequest;
use App\Exceptions\PaymentValidationException;

try {
    $request = new PaymentRequest(
        amount: -50.00,  // Monto negativo
        currency: 'EUR',
        orderId: 'TEST-001'
    );
} catch (PaymentValidationException $e) {
    echo "✅ Validación funcionando\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
}
```

---

### 4️⃣ Test de Respuesta HTTP

Prueba desde el frontend o con curl:

```bash
# Test con credenciales inválidas (simular)
curl -X POST http://localhost:8000/payments/stripe/initiate \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 50.00
  }'
```

**Con excepciones implementadas, obtendrás:**
```json
{
  "success": false,
  "message": "Service temporarily unavailable. Please contact support."
}
```
**HTTP Status:** 503

**Antes obtenías:**
```json
{
  "success": false,
  "message": "Stripe API key not configured. Set STRIPE_SECRET_KEY..."
}
```
**HTTP Status:** 500

---

### 5️⃣ Test de Logging

```php
use Illuminate\Support\Facades\Log;
use App\Services\Payments\StripePaymentService;
use App\Exceptions\PaymentProviderException;

Log::shouldReceive('critical')
    ->once()
    ->with('Config error', \Mockery::type('array'));

try {
    config(['payments.stripe.secret_key' => null]);
    $service = new StripePaymentService();
} catch (\Exception $e) {
    Log::critical('Config error', $e->toArray());
}
```

**Verifica en:** `storage/logs/laravel.log`

```
[2025-12-02 10:30:45] local.CRITICAL: Config error 
{
  "error": true,
  "message": "Missing secret_key for Stripe...",
  "code": 1001,
  "context": {
    "provider": "Stripe",
    "credential": "secret_key",
    "config_key": "payments.stripe.secret_key"
  }
}
```

---

### 6️⃣ Test Unitario Completo

Crea: `tests/Unit/Exceptions/PaymentExceptionTest.php`

```php
<?php

namespace Tests\Unit\Exceptions;

use App\Enums\PaymentProvider;
use App\Exceptions\PaymentConfigurationException;
use App\Exceptions\PaymentProviderException;
use App\Exceptions\PaymentValidationException;
use App\Exceptions\InvalidPaymentStateException;
use Tests\TestCase;

class PaymentExceptionTest extends TestCase
{
    /** @test */
    public function it_creates_missing_credentials_exception()
    {
        $exception = PaymentConfigurationException::missingCredentials('Stripe', 'api_key');
        
        $this->assertEquals(1001, $exception->getCode());
        $this->assertStringContainsString('Stripe', $exception->getMessage());
        $this->assertEquals('Stripe', $exception->getContext()['provider']);
        $this->assertEquals('api_key', $exception->getContext()['credential']);
    }

    /** @test */
    public function it_creates_payment_not_found_exception()
    {
        $exception = PaymentProviderException::paymentNotFound(
            PaymentProvider::STRIPE,
            'pi_123'
        );
        
        $this->assertEquals(2007, $exception->getCode());
        $this->assertEquals(404, $exception->getHttpStatusCode());
        $this->assertEquals('pi_123', $exception->getContext()['payment_id']);
    }

    /** @test */
    public function it_creates_invalid_amount_exception()
    {
        $exception = PaymentValidationException::invalidAmount(-50.00, 'Must be positive');
        
        $this->assertEquals(3001, $exception->getCode());
        $this->assertEquals(422, $exception->getHttpStatusCode());
        $this->assertEquals(-50.00, $exception->getContext()['amount']);
    }

    /** @test */
    public function it_creates_cannot_refund_exception()
    {
        $exception = InvalidPaymentStateException::cannotRefund('pi_123', 'pending');
        
        $this->assertEquals(4002, $exception->getCode());
        $this->assertEquals(409, $exception->getHttpStatusCode());
        $this->assertEquals('pending', $exception->getContext()['current_state']);
    }

    /** @test */
    public function exception_converts_to_array()
    {
        $exception = PaymentConfigurationException::missingCredentials('PayPal', 'client_id');
        
        $array = $exception->toArray();
        
        $this->assertTrue($array['error']);
        $this->assertIsString($array['message']);
        $this->assertEquals(1001, $array['code']);
        $this->assertIsArray($array['context']);
    }

    /** @test */
    public function exception_with_context_chainable()
    {
        $exception = PaymentProviderException::apiError(
            PaymentProvider::STRIPE,
            'Test error',
            null
        );
        
        $exception->withContext(['additional' => 'data']);
        
        $context = $exception->getContext();
        $this->assertEquals('data', $context['additional']);
    }

    /** @test */
    public function exception_renders_http_response()
    {
        $exception = PaymentValidationException::invalidCurrency('US');
        
        $response = $exception->render();
        
        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
    }
}
```

**Ejecutar tests:**
```bash
php artisan test --filter PaymentExceptionTest
```

---

### 7️⃣ Test de Integración

Crea: `tests/Feature/PaymentExceptionHandlingTest.php`

```php
<?php

namespace Tests\Feature;

use App\Enums\PaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentExceptionHandlingTest extends TestCase
{
    /** @test */
    public function stripe_initiate_handles_configuration_error_gracefully()
    {
        // Simular configuración inválida
        config(['payments.stripe.secret_key' => null]);

        $response = $this->postJson('/payments/stripe/initiate', [
            'amount' => 50.00,
        ]);

        $response->assertStatus(503);
        $response->assertJson([
            'success' => false,
            'message' => 'Service temporarily unavailable. Please contact support.',
        ]);
    }

    /** @test */
    public function refund_handles_payment_not_found()
    {
        $response = $this->postJson('/payments/refund/process', [
            'provider' => 'stripe',
            'payment_id' => 'pi_invalid_123',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
        ]);
        
        // Verificar que el mensaje no expone detalles técnicos
        $this->assertStringNotContainsString('pi_invalid_123', $response->json('message'));
    }
}
```

---

### 8️⃣ Verificar Logs Estructurados

```bash
# Ver los últimos logs
tail -f storage/logs/laravel.log

# Filtrar solo errores de pago
grep "Payment" storage/logs/laravel.log

# Ver logs en formato legible
php artisan pail
```

---

## 🎯 Checklist de Verificación

Después de implementar las excepciones, verifica:

- [ ] ✅ Las excepciones se lanzan correctamente en servicios
- [ ] ✅ El controlador captura excepciones específicas
- [ ] ✅ Los códigos HTTP son apropiados (500, 503, 422, 502, 404, 409)
- [ ] ✅ Los mensajes no exponen información sensible al frontend
- [ ] ✅ El logging incluye contexto completo con `toArray()`
- [ ] ✅ Los tests unitarios pasan
- [ ] ✅ Los tests de integración pasan
- [ ] ✅ No hay errores de linting

---

## 🔍 Debugging

Si algo no funciona:

### Ver todas las excepciones disponibles
```bash
ls -la app/Exceptions/
```

### Verificar imports en servicios
```bash
grep "use App\\\\Exceptions" app/Services/Payments/*.php
```

### Comprobar que los servicios usan las excepciones
```bash
grep "throw Payment" app/Services/Payments/*.php
```

### Ver manejo en controlador
```bash
grep "catch" app/Http/Controllers/PaymentController.php
```

---

## 📊 Métricas de Éxito

**Antes de las excepciones:**
- ❌ Todos los errores: HTTP 500
- ❌ Logging genérico
- ❌ Difícil determinar el tipo de error
- ❌ Tests complicados

**Después de las excepciones:**
- ✅ Códigos HTTP específicos (500, 503, 422, 502, 404, 409)
- ✅ Logging estructurado con contexto
- ✅ Códigos de error únicos (1001-4999)
- ✅ Tests simples y claros
- ✅ Monitoreo mejorado

---

## 🎓 Comandos Útiles

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar solo tests de excepciones
php artisan test --filter Exception

# Ver logs en tiempo real
php artisan pail

# Limpiar logs
rm storage/logs/*.log

# Verificar sintaxis PHP
php -l app/Exceptions/*.php

# Ejecutar linter
./vendor/bin/pint
```

---

## 🚀 Próximos Pasos

Una vez verificadas las excepciones:

1. ✅ Implementar validación en DTOs (Punto 3)
2. ✅ Añadir logging avanzado (Punto 4)
3. ✅ Crear tests completos (Punto 5)
4. ✅ Preparar para el paquete

---

**¡El sistema de excepciones está listo para testing!** 🎉

