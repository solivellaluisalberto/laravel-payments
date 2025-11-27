@extends('layouts.app')

@section('title', 'Sistema de Pagos Multi-Proveedor')

@section('content')
<div class="header">
    <h1>💳 Sistema de Pagos Multi-Proveedor</h1>
    <p>Ejemplos de integración con Stripe, Redsys y PayPal en Laravel</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
    {{-- Stripe --}}
    <div class="card">
        <div style="font-size: 48px; margin-bottom: 15px;">💳</div>
        <h2 style="margin-bottom: 10px;">Stripe</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Pago moderno con API. El usuario completa el pago sin salir de tu página.
        </p>
        <p style="margin-bottom: 20px;"><strong>Flujo:</strong> API REST + JavaScript</p>
        <a href="{{ route('payments.stripe.example') }}" class="btn">Probar Stripe →</a>
    </div>

    {{-- Redsys --}}
    <div class="card">
        <div style="font-size: 48px; margin-bottom: 15px;">🏦</div>
        <h2 style="margin-bottom: 10px;">Redsys</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Pago tradicional con redirección al TPV del banco. Soporta Tarjeta y Bizum.
        </p>
        <p style="margin-bottom: 20px;"><strong>Flujo:</strong> Redirección + Callback</p>
        <a href="{{ route('payments.redsys.example') }}" class="btn">Probar Redsys →</a>
    </div>

    {{-- PayPal --}}
    <div class="card">
        <div style="font-size: 48px; margin-bottom: 15px;">💰</div>
        <h2 style="margin-bottom: 10px;">PayPal</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Pago con PayPal. Redirección a PayPal y retorno automático.
        </p>
        <p style="margin-bottom: 20px;"><strong>Flujo:</strong> SDK Oficial</p>
        <a href="{{ route('payments.paypal.example') }}" class="btn">Probar PayPal →</a>
    </div>

    {{-- Reembolsos --}}
    <div class="card">
        <div style="font-size: 48px; margin-bottom: 15px;">↩️</div>
        <h2 style="margin-bottom: 10px;">Reembolsos</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Aprende cómo hacer devoluciones con Stripe, Redsys y PayPal.
        </p>
        <p style="margin-bottom: 20px;"><strong>Soporte:</strong> Todos los proveedores</p>
        <a href="{{ route('payments.refund.example') }}" class="btn btn-secondary">Ver Reembolsos →</a>
    </div>
    
    {{-- Comparativa --}}
    <div class="card">
        <div style="font-size: 48px; margin-bottom: 15px;">📊</div>
        <h2 style="margin-bottom: 10px;">Comparativa</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Compara las diferencias entre Stripe, Redsys y PayPal.
        </p>
        <p style="margin-bottom: 20px;"><strong>Info:</strong> Flujos, ventajas y desventajas</p>
        <a href="{{ route('payments.comparative') }}" class="btn btn-secondary">Ver Comparativa →</a>
    </div>
    
    {{-- Eventos --}}
    <div class="card" style="border-left: 4px solid #4CAF50;">
        <div style="font-size: 48px; margin-bottom: 15px;">🎯</div>
        <h2 style="margin-bottom: 10px;">Sistema de Eventos</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Acciones automáticas post-pago: guardar en BD, enviar emails, actualizar inventario.
        </p>
        <p style="margin-bottom: 20px;"><strong>Ventaja:</strong> Código común para todos los proveedores</p>
        <a href="{{ route('payments.events') }}" class="btn" style="background: #4CAF50;">Ver Documentación →</a>
    </div>
</div>

<div class="card" style="margin-top: 30px;">
    <h2 style="margin-bottom: 15px;">🏗️ Arquitectura</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-top: 20px;">
        <div>
            <h3 style="margin-bottom: 10px;">📦 DTOs</h3>
            <ul style="list-style: none; line-height: 2;">
                <li>✓ PaymentRequest</li>
                <li>✓ PaymentResponse</li>
                <li>✓ PaymentResult</li>
            </ul>
        </div>
        <div>
            <h3 style="margin-bottom: 10px;">⚙️ Services</h3>
            <ul style="list-style: none; line-height: 2;">
                <li>✓ PaymentGateway (interface)</li>
                <li>✓ PaymentManager (factory)</li>
                <li>✓ StripePaymentService</li>
                <li>✓ RedsysPaymentService</li>
                <li>✓ PayPalPaymentService</li>
            </ul>
        </div>
        <div>
            <h3 style="margin-bottom: 10px;">🏷️ Enums</h3>
            <ul style="list-style: none; line-height: 2;">
                <li>✓ PaymentProvider</li>
                <li>✓ PaymentMethod</li>
                <li>✓ PaymentState</li>
                <li>✓ PaymentType</li>
            </ul>
        </div>
        <div>
            <h3 style="margin-bottom: 10px;">📢 Eventos</h3>
            <ul style="list-style: none; line-height: 2;">
                <li>✓ PaymentCompleted</li>
            </ul>
            <p style="color: #666; font-size: 13px; margin-top: 10px; font-style: italic;">
                Se disparan cuando ocurre un pago exitoso
            </p>
        </div>
        <div>
            <h3 style="margin-bottom: 10px;">👂 Listeners</h3>
            <ul style="list-style: none; line-height: 2;">
                <li>✓ LogPaymentToDatabase</li>
                <li>✓ SendConfirmationEmail</li>
                <li>✓ SendAdminNotification</li>
                <li>✓ UpdateInventory</li>
            </ul>
            <p style="color: #666; font-size: 13px; margin-top: 10px; font-style: italic;">
                Escuchan eventos y ejecutan acciones
            </p>
        </div>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #4CAF50;">
        <strong>💡 Patrón Strategy:</strong> Cada proveedor implementa <code>PaymentGateway</code> con su lógica específica.<br>
        <strong>🏭 Factory Pattern:</strong> <code>PaymentManager</code> crea y cachea las instancias de servicios.<br>
        <strong>💉 Dependency Injection:</strong> Laravel inyecta automáticamente las dependencias.<br>
        <strong>📢 Event System:</strong> Los <code>Eventos</code> se disparan cuando un pago se completa.<br>
        <strong>👂 Listeners:</strong> Escuchan eventos y ejecutan acciones post-pago automáticamente.
    </div>
</div>
@endsection

