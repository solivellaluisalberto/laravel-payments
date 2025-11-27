@extends('layouts.app')

@section('title', 'Comparativa de Proveedores')

@push('styles')
<style>
    .comparison-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .comparison-table th,
    .comparison-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .comparison-table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
    }
    
    .comparison-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .check {
        color: #28a745;
        font-size: 18px;
    }
    
    .cross {
        color: #dc3545;
        font-size: 18px;
    }
    
    .warning {
        color: #ffc107;
        font-size: 18px;
    }
    
    .provider-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .provider-card h3 {
        margin-bottom: 15px;
        color: #667eea;
    }
    
    .feature-list {
        list-style: none;
        padding: 0;
    }
    
    .feature-list li {
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .feature-list li:last-child {
        border-bottom: none;
    }
    
    .code-example {
        background: #2d2d2d;
        color: #f8f8f2;
        padding: 20px;
        border-radius: 8px;
        overflow-x: auto;
        margin-top: 15px;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.8;
        white-space: pre;
    }
    
    .code-example pre {
        margin: 0;
        padding: 0;
        white-space: pre;
        overflow-x: auto;
    }
    
    .code-example .comment {
        color: #6a9955;
    }
    
    .code-example .keyword {
        color: #569cd6;
    }
    
    .code-example .string {
        color: #ce9178;
    }
    
    .code-example .function {
        color: #dcdcaa;
    }
</style>
@endpush

@section('content')
<div class="header">
    <h1>📊 Comparativa de Proveedores de Pago</h1>
    <p>Diferencias clave entre Stripe, Redsys y PayPal</p>
</div>

{{-- Tabla Comparativa --}}
<div class="card">
    <h2 style="margin-bottom: 20px;">Comparación General</h2>
    
    <table class="comparison-table">
        <thead>
            <tr>
                <th>Característica</th>
                <th style="text-align: center;">💳 Stripe</th>
                <th style="text-align: center;">🏦 Redsys</th>
                <th style="text-align: center;">💰 PayPal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Tipo de Integración</strong></td>
                <td style="text-align: center;">API REST + JS</td>
                <td style="text-align: center;">Formulario + Redirección</td>
                <td style="text-align: center;">SDK + Redirección</td>
            </tr>
            <tr>
                <td><strong>Flujo de Pago</strong></td>
                <td style="text-align: center;">En tu sitio</td>
                <td style="text-align: center;">TPV externo</td>
                <td style="text-align: center;">Sitio de PayPal</td>
            </tr>
            <tr>
                <td><strong>Experiencia Usuario</strong></td>
                <td style="text-align: center;"><span class="check">✅</span> Excelente</td>
                <td style="text-align: center;"><span class="warning">⚠️</span> Buena</td>
                <td style="text-align: center;"><span class="check">✅</span> Buena</td>
            </tr>
            <tr>
                <td><strong>Reembolsos API</strong></td>
                <td style="text-align: center;"><span class="check">✅</span> Sí</td>
                <td style="text-align: center;"><span class="check">✅</span> Sí</td>
                <td style="text-align: center;"><span class="check">✅</span> Sí</td>
            </tr>
            <tr>
                <td><strong>Pagos Parciales</strong></td>
                <td style="text-align: center;"><span class="check">✅</span> Sí</td>
                <td style="text-align: center;"><span class="cross">❌</span> No</td>
                <td style="text-align: center;"><span class="check">✅</span> Sí</td>
            </tr>
            <tr>
                <td><strong>Suscripciones</strong></td>
                <td style="text-align: center;"><span class="check">✅</span> Nativo</td>
                <td style="text-align: center;"><span class="warning">⚠️</span> Limitado</td>
                <td style="text-align: center;"><span class="check">✅</span> Nativo</td>
            </tr>
            <tr>
                <td><strong>Webhooks</strong></td>
                <td style="text-align: center;"><span class="check">✅</span> Completos</td>
                <td style="text-align: center;"><span class="check">✅</span> Básicos</td>
                <td style="text-align: center;"><span class="check">✅</span> Completos</td>
            </tr>
            <tr>
                <td><strong>Documentación</strong></td>
                <td style="text-align: center;"><span class="check">✅</span> Excelente</td>
                <td style="text-align: center;"><span class="warning">⚠️</span> Regular</td>
                <td style="text-align: center;"><span class="check">✅</span> Muy Buena</td>
            </tr>
            <tr>
                <td><strong>Testing</strong></td>
                <td style="text-align: center;"><span class="check">✅</span> Tarjetas test</td>
                <td style="text-align: center;"><span class="check">✅</span> Entorno test</td>
                <td style="text-align: center;"><span class="check">✅</span> Sandbox</td>
            </tr>
            <tr>
                <td><strong>Comisiones</strong></td>
                <td style="text-align: center;">1.4% + 0.25€</td>
                <td style="text-align: center;">Variable (banco)</td>
                <td style="text-align: center;">2.9% + 0.35€</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Stripe --}}
<div class="provider-card">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
        <div style="font-size: 48px;">💳</div>
        <div>
            <h3 style="margin: 0;">Stripe</h3>
            <p style="margin: 5px 0 0 0; color: #666;">Pagos modernos con API</p>
        </div>
    </div>
    
    <ul class="feature-list">
        <li><strong>✅ Ventajas:</strong> Experiencia de usuario fluida, sin redirecciones, documentación excelente, soporte internacional</li>
        <li><strong>❌ Desventajas:</strong> Comisiones más altas que Redsys, requiere verificación de cuenta</li>
        <li><strong>💡 Ideal para:</strong> Startups, SaaS, e-commerce moderno, suscripciones</li>
        <li><strong>🌍 Métodos de pago:</strong> Tarjetas, Apple Pay, Google Pay, Alipay, iDEAL, etc.</li>
    </ul>
    
    <div class="code-example"><pre><span class="comment">// Ejemplo de código Stripe</span>
<span class="keyword">$gateway</span> = <span class="keyword">$manager</span>-><span class="function">driver</span>(<span class="keyword">PaymentProvider</span>::<span class="string">STRIPE</span>);

<span class="keyword">$response</span> = <span class="keyword">$gateway</span>-><span class="function">initiate</span>(<span class="keyword">new</span> <span class="function">PaymentRequest</span>(
    amount: <span class="string">50.00</span>,
    currency: <span class="string">'EUR'</span>,
    orderId: <span class="string">'ORDER-123'</span>
));

<span class="comment">// Devuelve clientSecret para usar con Stripe.js en frontend</span>
<span class="keyword">return</span> <span class="function">view</span>(<span class="string">'checkout'</span>, [
    <span class="string">'clientSecret'</span> => <span class="keyword">$response</span>->clientSecret
]);</pre>
    </div>
</div>

{{-- Redsys --}}
<div class="provider-card">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
        <div style="font-size: 48px;">🏦</div>
        <div>
            <h3 style="margin: 0;">Redsys</h3>
            <p style="margin: 5px 0 0 0; color: #666;">TPV de bancos españoles</p>
        </div>
    </div>
    
    <ul class="feature-list">
        <li><strong>✅ Ventajas:</strong> Comisiones más bajas, integración con banco español, múltiples métodos (Bizum, tarjeta)</li>
        <li><strong>❌ Desventajas:</strong> Redirección obligatoria, documentación limitada, UX menos moderna</li>
        <li><strong>💡 Ideal para:</strong> E-commerce español, negocios con contrato bancario existente</li>
        <li><strong>🌍 Métodos de pago:</strong> Tarjetas (Visa, Mastercard), Bizum, domiciliación</li>
    </ul>
    
    <div class="code-example"><pre><span class="comment">// Ejemplo de código Redsys</span>
<span class="keyword">$gateway</span> = <span class="keyword">$manager</span>-><span class="function">driver</span>(<span class="keyword">PaymentProvider</span>::<span class="string">REDSYS</span>);

<span class="keyword">$response</span> = <span class="keyword">$gateway</span>-><span class="function">initiate</span>(<span class="keyword">new</span> <span class="function">PaymentRequest</span>(
    amount: <span class="string">50.00</span>,
    currency: <span class="string">'EUR'</span>,
    orderId: <span class="function">str_pad</span>(<span class="function">time</span>(), <span class="string">12</span>, <span class="string">'0'</span>),
    paymentMethod: <span class="keyword">PaymentMethod</span>::<span class="string">BIZUM</span> <span class="comment">// o CARD</span>
));

<span class="comment">// Devuelve formulario HTML que se auto-envía al TPV</span>
<span class="keyword">return</span> <span class="function">view</span>(<span class="string">'redsys-redirect'</span>, [
    <span class="string">'formHtml'</span> => <span class="keyword">$response</span>->formHtml
]);</pre>
    </div>
</div>

{{-- PayPal --}}
<div class="provider-card">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
        <div style="font-size: 48px;">💰</div>
        <div>
            <h3 style="margin: 0;">PayPal</h3>
            <p style="margin: 5px 0 0 0; color: #666;">Pagos reconocidos mundialmente</p>
        </div>
    </div>
    
    <ul class="feature-list">
        <li><strong>✅ Ventajas:</strong> Marca reconocida, fácil para usuarios con cuenta PayPal, protección comprador/vendedor</li>
        <li><strong>❌ Desventajas:</strong> Comisiones altas, proceso de aprobación de cuenta, disputas frecuentes</li>
        <li><strong>💡 Ideal para:</strong> Marketplace, ventas internacionales, usuarios que prefieren PayPal</li>
        <li><strong>🌍 Métodos de pago:</strong> Cuenta PayPal, tarjetas (si no tienen cuenta)</li>
    </ul>
    
    <div class="code-example"><pre><span class="comment">// Ejemplo de código PayPal</span>
<span class="keyword">$gateway</span> = <span class="keyword">$manager</span>-><span class="function">driver</span>(<span class="keyword">PaymentProvider</span>::<span class="string">PAYPAL</span>);

<span class="keyword">$response</span> = <span class="keyword">$gateway</span>-><span class="function">initiate</span>(<span class="keyword">new</span> <span class="function">PaymentRequest</span>(
    amount: <span class="string">50.00</span>,
    currency: <span class="string">'EUR'</span>,
    orderId: <span class="string">'ORDER-123'</span>,
    returnUrl: <span class="function">route</span>(<span class="string">'payments.paypal.return'</span>)
));

<span class="comment">// Redirige al usuario a PayPal para aprobar el pago</span>
<span class="keyword">return</span> <span class="function">redirect</span>(<span class="keyword">$response</span>->redirectUrl);</pre>
    </div>
</div>

{{-- Comparativa de Flujos --}}
<div class="card">
    <h2 style="margin-bottom: 20px;">🔄 Comparativa de Flujos de Pago</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        {{-- Stripe --}}
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
            <h3 style="color: #667eea; margin-bottom: 15px;">💳 Stripe</h3>
            <ol style="line-height: 2; color: #666;">
                <li>Usuario introduce tarjeta</li>
                <li>JS envía datos a Stripe</li>
                <li>Backend crea Payment Intent</li>
                <li>Frontend confirma con Stripe.js</li>
                <li>Pago completado ✅</li>
                <li>Sin salir de tu sitio</li>
            </ol>
            <div style="margin-top: 15px; padding: 10px; background: white; border-radius: 4px;">
                <strong>⏱️ Tiempo:</strong> ~2 segundos<br>
                <strong>📱 UX:</strong> Excelente
            </div>
        </div>
        
        {{-- Redsys --}}
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #ff6b6b;">
            <h3 style="color: #ff6b6b; margin-bottom: 15px;">🏦 Redsys</h3>
            <ol style="line-height: 2; color: #666;">
                <li>Backend genera formulario firmado</li>
                <li>Usuario es redirigido al TPV</li>
                <li>Introduce datos en TPV banco</li>
                <li>Banco procesa pago</li>
                <li>Redirige de vuelta a tu sitio</li>
                <li>Backend verifica firma</li>
            </ol>
            <div style="margin-top: 15px; padding: 10px; background: white; border-radius: 4px;">
                <strong>⏱️ Tiempo:</strong> ~30 segundos<br>
                <strong>📱 UX:</strong> Buena (redirección)
            </div>
        </div>
        
        {{-- PayPal --}}
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #0070ba;">
            <h3 style="color: #0070ba; margin-bottom: 15px;">💰 PayPal</h3>
            <ol style="line-height: 2; color: #666;">
                <li>Backend crea orden PayPal</li>
                <li>Usuario redirigido a PayPal</li>
                <li>Login y aprueba pago</li>
                <li>PayPal redirige de vuelta</li>
                <li>Backend captura pago</li>
                <li>Pago completado ✅</li>
            </ol>
            <div style="margin-top: 15px; padding: 10px; background: white; border-radius: 4px;">
                <strong>⏱️ Tiempo:</strong> ~20 segundos<br>
                <strong>📱 UX:</strong> Buena (familiar)
            </div>
        </div>
    </div>
</div>

{{-- Recomendaciones --}}
<div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
    <h2 style="color: white; margin-bottom: 20px;">💡 ¿Cuál Elegir?</h2>
    
    <div style="display: grid; gap: 15px;">
        <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
            <h3 style="color: white;">Elige Stripe si...</h3>
            <ul style="line-height: 2;">
                <li>Quieres la mejor experiencia de usuario</li>
                <li>Vendes internacionalmente</li>
                <li>Necesitas suscripciones o pagos recurrentes</li>
                <li>Tienes una startup o SaaS</li>
            </ul>
        </div>
        
        <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
            <h3 style="color: white;">Elige Redsys si...</h3>
            <ul style="line-height: 2;">
                <li>Tu negocio es principalmente español</li>
                <li>Quieres comisiones más bajas</li>
                <li>Necesitas soporte de Bizum</li>
                <li>Ya tienes contrato con un banco español</li>
            </ul>
        </div>
        
        <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
            <h3 style="color: white;">Elige PayPal si...</h3>
            <ul style="line-height: 2;">
                <li>Tus clientes prefieren usar PayPal</li>
                <li>Vendes en marketplace o eBay</li>
                <li>Necesitas protección contra disputas</li>
                <li>Quieres aprovechar la marca PayPal</li>
            </ul>
        </div>
        
        <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px; border: 2px solid white;">
            <h3 style="color: white;">🎯 Recomendación: Ofrece Múltiples Opciones</h3>
            <p style="line-height: 1.8;">
                La mejor estrategia es <strong>ofrecer varios métodos de pago</strong>. 
                Usa Stripe como principal, añade Redsys para Bizum, y PayPal como alternativa. 
                Gracias a la arquitectura con Strategy Pattern, es muy fácil tener múltiples proveedores.
            </p>
        </div>
    </div>
</div>

{{-- Botones de Prueba --}}
<div class="card">
    <h2 style="margin-bottom: 20px; text-align: center;">🧪 Prueba Cada Proveedor</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="{{ route('payments.stripe.example') }}" class="btn" style="text-align: center; text-decoration: none;">Probar Stripe →</a>
        <a href="{{ route('payments.redsys.example') }}" class="btn" style="text-align: center; text-decoration: none;">Probar Redsys →</a>
        <a href="{{ route('payments.paypal.example') }}" class="btn" style="text-align: center; text-decoration: none;">Probar PayPal →</a>
    </div>
</div>
@endsection

