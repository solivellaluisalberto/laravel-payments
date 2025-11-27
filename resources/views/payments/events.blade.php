@extends('layouts.app')

@section('title', 'Sistema de Eventos de Pagos')

@push('styles')
<style>
    .flow-diagram {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin: 20px 0;
    }
    
    .flow-step {
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255,255,255,0.3);
        padding: 20px;
        border-radius: 8px;
        margin: 15px 0;
        position: relative;
    }
    
    .flow-step::after {
        content: '↓';
        font-size: 36px;
        display: block;
        text-align: center;
        margin: 10px 0 -10px;
    }
    
    .flow-step:last-child::after {
        content: '';
    }
    
    .listener-card {
        background: #f8f9fa;
        border-left: 4px solid #667eea;
        padding: 20px;
        border-radius: 8px;
        margin: 15px 0;
    }
    
    .listener-card.sync {
        border-left-color: #ff6b6b;
    }
    
    .listener-card.async {
        border-left-color: #4ecdc4;
    }
    
    .code-block {
        background: #2d3748;
        color: #e2e8f0;
        padding: 20px;
        border-radius: 8px;
        overflow-x: auto;
        margin: 15px 0;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.8;
        white-space: pre;
    }
    
    .code-block pre {
        margin: 0;
        padding: 0;
        white-space: pre;
        overflow-x: auto;
    }
    
    .code-block .keyword { color: #c678dd; }
    .code-block .string { color: #98c379; }
    .code-block .comment { color: #5c6370; font-style: italic; }
    .code-block .function { color: #61afef; }
    .code-block .variable { color: #e06c75; }
    
    .comparison-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    
    .comparison-table th,
    .comparison-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .comparison-table th {
        background: #667eea;
        color: white;
        font-weight: bold;
    }
    
    .comparison-table tr:hover {
        background: #f8f9fa;
    }
    
    .tag {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        margin-right: 5px;
    }
    
    .tag.sync {
        background: #ffe0e0;
        color: #ff6b6b;
    }
    
    .tag.async {
        background: #d4f4f2;
        color: #4ecdc4;
    }
</style>
@endpush

@section('content')
<div class="header">
    <h1>🎯 Sistema de Eventos de Pagos</h1>
    <p>Cómo ejecutar acciones automáticas al completar pagos, sin importar el proveedor</p>
</div>

{{-- Problema y Solución --}}
<div class="card">
    <h2 style="margin-bottom: 20px;">🤔 ¿Qué Problema Resuelve?</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        <div style="background: #fff5f5; padding: 20px; border-radius: 8px; border-left: 4px solid #ff6b6b;">
            <h3 style="color: #ff6b6b; margin-bottom: 15px;">❌ Antes (Sin Eventos)</h3>
            <div class="code-block" style="font-size: 12px;"><pre><span class="comment">// En StripeController</span>
<span class="keyword">if</span> (<span class="variable">$result</span>-><span class="variable">success</span>) {
    Payment::<span class="function">create</span>([...]);
    Mail::<span class="function">to</span>(<span class="variable">$customer</span>)-><span class="function">send</span>(...);
    Notification::<span class="function">send</span>(<span class="variable">$admin</span>, ...);
    Inventory::<span class="function">reduce</span>(...);
}

<span class="comment">// En RedsysController</span>
<span class="keyword">if</span> (<span class="variable">$result</span>-><span class="variable">success</span>) {
    Payment::<span class="function">create</span>([...]);           <span class="comment">// DUPLICADO</span>
    Mail::<span class="function">to</span>(<span class="variable">$customer</span>)-><span class="function">send</span>(...);   <span class="comment">// DUPLICADO</span>
    Notification::<span class="function">send</span>(<span class="variable">$admin</span>, ...);  <span class="comment">// DUPLICADO</span>
    Inventory::<span class="function">reduce</span>(...);           <span class="comment">// DUPLICADO</span>
}

<span class="comment">// Y así con cada proveedor... 😫</span></pre>
            </div>
            <p style="color: #666; margin-top: 15px;">
                <strong>Problemas:</strong> Código duplicado, difícil de mantener, mezcla lógica de negocio.
            </p>
        </div>
        
        <div style="background: #f0fff4; padding: 20px; border-radius: 8px; border-left: 4px solid #4CAF50;">
            <h3 style="color: #4CAF50; margin-bottom: 15px;">✅ Ahora (Con Eventos)</h3>
            <div class="code-block" style="font-size: 12px;"><pre><span class="comment">// En TODOS los controllers</span>
<span class="keyword">if</span> (<span class="variable">$result</span>-><span class="variable">success</span>) {
    <span class="function">event</span>(<span class="keyword">new</span> <span class="function">PaymentCompleted</span>(
        <span class="variable">provider</span>: PaymentProvider::STRIPE,
        <span class="variable">result</span>: <span class="variable">$result</span>,
        <span class="variable">orderId</span>: <span class="variable">$orderId</span>,
        <span class="variable">amount</span>: <span class="variable">$amount</span>,
        <span class="variable">currency</span>: <span class="string">'EUR'</span>
    ));
}

<span class="comment">// ✨ Los listeners se ejecutan automáticamente:</span>
<span class="comment">//  → LogPaymentToDatabase</span>
<span class="comment">//  → SendConfirmationEmail</span>
<span class="comment">//  → SendAdminNotification</span>
<span class="comment">//  → UpdateInventory</span></pre>
            </div>
            <p style="color: #666; margin-top: 15px;">
                <strong>Ventajas:</strong> Sin duplicación, fácil de mantener, separación de responsabilidades.
            </p>
        </div>
    </div>
</div>

{{-- Flujo Visual --}}
<div class="card">
    <h2 style="margin-bottom: 20px;">🔄 Flujo de Ejecución</h2>
    
    <div class="flow-diagram">
        <div class="flow-step">
            <h3 style="margin: 0 0 10px 0;">1️⃣ Usuario Completa el Pago</h3>
            <p style="margin: 0; opacity: 0.9;">En Stripe, Redsys o PayPal</p>
        </div>
        
        <div class="flow-step">
            <h3 style="margin: 0 0 10px 0;">2️⃣ Controller Captura el Resultado</h3>
            <code style="background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 4px;">
                $result = $gateway->capture($paymentId);
            </code>
        </div>
        
        <div class="flow-step">
            <h3 style="margin: 0 0 10px 0;">3️⃣ Si Exitoso, Dispara el Evento</h3>
            <code style="background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 4px;">
                event(new PaymentCompleted(...));
            </code>
        </div>
        
        <div class="flow-step">
            <h3 style="margin: 0 0 10px 0;">4️⃣ Laravel Ejecuta los Listeners</h3>
            <div style="margin-top: 10px; padding-left: 20px;">
                <p style="margin: 5px 0;">⚡ LogPaymentToDatabase (inmediato)</p>
                <p style="margin: 5px 0;">⚡ UpdateInventory (inmediato)</p>
                <p style="margin: 5px 0;">⏱️ SendConfirmationEmail (cola)</p>
                <p style="margin: 5px 0;">⏱️ SendAdminNotification (cola)</p>
            </div>
        </div>
        
        <div class="flow-step">
            <h3 style="margin: 0 0 10px 0;">5️⃣ Usuario Recibe Respuesta</h3>
            <p style="margin: 0; opacity: 0.9;">Página de éxito o error</p>
        </div>
        
        <div class="flow-step" style="border: 2px dashed rgba(255,255,255,0.5);">
            <h3 style="margin: 0 0 10px 0;">6️⃣ Workers Procesan Cola (Background)</h3>
            <p style="margin: 0; opacity: 0.9; font-size: 14px;">
                Segundos después, los listeners asíncronos se ejecutan sin bloquear la respuesta
            </p>
        </div>
    </div>
</div>

{{-- Componentes --}}
<div class="card">
    <h2 style="margin-bottom: 20px;">🧩 Componentes del Sistema</h2>
    
    <h3 style="margin: 30px 0 15px 0; color: #667eea;">📢 Evento: PaymentCompleted</h3>
    <p style="color: #666; margin-bottom: 15px;">
        Encapsula toda la información de un pago completado, independiente del proveedor.
    </p>
    
    <div class="code-block"><pre><span class="comment">// app/Events/PaymentCompleted.php</span>

<span class="function">event</span>(<span class="keyword">new</span> <span class="function">PaymentCompleted</span>(
    <span class="variable">provider</span>: PaymentProvider::STRIPE,        <span class="comment">// STRIPE, REDSYS, PAYPAL</span>
    <span class="variable">result</span>: <span class="variable">$paymentResult</span>,                   <span class="comment">// Resultado del pago</span>
    <span class="variable">orderId</span>: <span class="string">'ORDER-123'</span>,                    <span class="comment">// ID de la orden</span>
    <span class="variable">amount</span>: <span class="string">99.99</span>,                             <span class="comment">// Cantidad pagada</span>
    <span class="variable">currency</span>: <span class="string">'EUR'</span>,                           <span class="comment">// Moneda</span>
    <span class="variable">metadata</span>: [<span class="string">'items'</span> => [...]],              <span class="comment">// Datos adicionales</span>
    <span class="variable">customerEmail</span>: <span class="string">'cliente@example.com'</span>      <span class="comment">// Email del cliente</span>
));</pre>
    </div>
    
    <h3 style="margin: 30px 0 15px 0; color: #667eea;">👂 Listeners (Escuchadores)</h3>
    <p style="color: #666; margin-bottom: 15px;">
        Clases que se ejecutan automáticamente cuando el evento se dispara.
    </p>
    
    <div class="listener-card sync">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h4 style="margin: 0;">💾 LogPaymentToDatabase</h4>
            <span class="tag sync">⚡ SÍNCRONO</span>
        </div>
        <p style="color: #666; margin: 10px 0;">
            Guarda el pago en la base de datos. Se ejecuta <strong>inmediatamente</strong> porque es crítico.
        </p>
        <div class="code-block" style="font-size: 12px;"><pre>Payment::<span class="function">create</span>([
    <span class="string">'order_id'</span>     => <span class="variable">$event</span>-><span class="variable">orderId</span>,
    <span class="string">'payment_id'</span>   => <span class="variable">$event</span>-><span class="variable">result</span>-><span class="variable">paymentId</span>,
    <span class="string">'provider'</span>     => <span class="variable">$event</span>-><span class="variable">provider</span>,
    <span class="string">'amount'</span>       => <span class="variable">$event</span>-><span class="variable">amount</span>,
    <span class="string">'currency'</span>     => <span class="variable">$event</span>-><span class="variable">currency</span>,
    <span class="string">'completed_at'</span> => <span class="function">now</span>(),
]);</pre>
        </div>
    </div>
    
    <div class="listener-card async">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h4 style="margin: 0;">📧 SendPaymentConfirmationEmail</h4>
            <span class="tag async">⏱️ ASÍNCRONO</span>
        </div>
        <p style="color: #666; margin: 10px 0;">
            Envía email de confirmación al cliente. Se ejecuta en <strong>background</strong> para no bloquear la respuesta.
        </p>
        <div class="code-block" style="font-size: 12px;"><pre>Mail::<span class="function">to</span>(<span class="variable">$event</span>-><span class="variable">customerEmail</span>)
    -><span class="function">send</span>(<span class="keyword">new</span> <span class="function">PaymentConfirmationMail</span>(<span class="variable">$event</span>));

<span class="comment">// shouldQueue() retorna true</span>
<span class="comment">// → Se ejecuta en cola (background)</span></pre>
        </div>
    </div>
    
    <div class="listener-card async">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h4 style="margin: 0;">📢 SendAdminNotification</h4>
            <span class="tag async">⏱️ ASÍNCRONO</span>
        </div>
        <p style="color: #666; margin: 10px 0;">
            Notifica al administrador (email, Slack, SMS, etc.). También en background.
        </p>
        <div class="code-block" style="font-size: 12px;"><pre><span class="comment">// Puedes usar múltiples canales:</span>

<span class="comment">// Email</span>
Mail::<span class="function">to</span>(<span class="variable">$adminEmail</span>)-><span class="function">send</span>(...);

<span class="comment">// Slack</span>
Notification::<span class="function">route</span>(<span class="string">'slack'</span>, <span class="variable">$webhook</span>)
    -><span class="function">notify</span>(<span class="keyword">new</span> <span class="function">PaymentNotification</span>(<span class="variable">$event</span>));

<span class="comment">// SMS, WhatsApp, Discord, etc.</span></pre>
        </div>
    </div>
    
    <div class="listener-card sync">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h4 style="margin: 0;">📦 UpdateInventory</h4>
            <span class="tag sync">⚡ SÍNCRONO</span>
        </div>
        <p style="color: #666; margin: 10px 0;">
            Actualiza inventario, activa suscripciones, reduce stock. Inmediato para evitar sobre-ventas.
        </p>
        <div class="code-block" style="font-size: 12px;"><pre><span class="variable">$items</span> = <span class="variable">$event</span>-><span class="variable">metadata</span>[<span class="string">'items'</span>] ?? [];

<span class="keyword">foreach</span> (<span class="variable">$items</span> <span class="keyword">as</span> <span class="variable">$item</span>) {
    <span class="variable">$product</span> = Product::<span class="function">find</span>(<span class="variable">$item</span>[<span class="string">'product_id'</span>]);
    
    <span class="keyword">if</span> (<span class="variable">$product</span>) {
        <span class="variable">$product</span>-><span class="function">decrement</span>(<span class="string">'stock'</span>, <span class="variable">$item</span>[<span class="string">'quantity'</span>]);
    }
}</pre>
        </div>
    </div>
</div>

{{-- Comparativa Síncrono vs Asíncrono --}}
<div class="card">
    <h2 style="margin-bottom: 20px;">⚡ Síncrono vs Asíncrono</h2>
    
    <table class="comparison-table">
        <thead>
            <tr>
                <th>Aspecto</th>
                <th>⚡ Síncrono</th>
                <th>⏱️ Asíncrono (Cola)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Ejecución</strong></td>
                <td>Inmediata, antes de responder</td>
                <td>Background, después de responder</td>
            </tr>
            <tr>
                <td><strong>Bloquea respuesta</strong></td>
                <td>✅ Sí</td>
                <td>❌ No</td>
            </tr>
            <tr>
                <td><strong>Velocidad</strong></td>
                <td>Ralentiza la respuesta</td>
                <td>Respuesta inmediata</td>
            </tr>
            <tr>
                <td><strong>Uso recomendado</strong></td>
                <td>Operaciones críticas (BD, stock)</td>
                <td>Emails, notificaciones, logs</td>
            </tr>
            <tr>
                <td><strong>Reintentos</strong></td>
                <td>No automáticos</td>
                <td>✅ Automáticos si falla</td>
            </tr>
            <tr>
                <td><strong>Ejemplos</strong></td>
                <td>LogPaymentToDatabase, UpdateInventory</td>
                <td>SendEmail, SendNotification</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Ventajas --}}
<div class="card">
    <h2 style="margin-bottom: 20px;">✨ Ventajas del Sistema</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6;">
            <h3 style="color: #3b82f6; margin-bottom: 10px;">🔄 Agnóstico del Proveedor</h3>
            <p style="color: #666;">
                El mismo evento funciona para Stripe, Redsys, PayPal y cualquier proveedor futuro.
                No importa cómo se hizo el pago.
            </p>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; border-left: 4px solid #22c55e;">
            <h3 style="color: #22c55e; margin-bottom: 10px;">➕ Fácil Añadir Acciones</h3>
            <p style="color: #666;">
                ¿Quieres enviar a Discord? Crea un listener, regístralo. ¡Listo! 
                Sin tocar controllers ni servicios.
            </p>
        </div>
        
        <div style="background: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b;">
            <h3 style="color: #f59e0b; margin-bottom: 10px;">🔌 Fácil Desactivar</h3>
            <p style="color: #666;">
                Comenta una línea en AppServiceProvider para desactivar cualquier acción.
                Sin borrar código.
            </p>
        </div>
        
        <div style="background: #fce7f3; padding: 20px; border-radius: 8px; border-left: 4px solid #ec4899;">
            <h3 style="color: #ec4899; margin-bottom: 10px;">🧪 Testing Simplificado</h3>
            <p style="color: #666;">
                Event::fake() te permite testear fácilmente que los eventos se disparan correctamente.
            </p>
        </div>
        
        <div style="background: #ede9fe; padding: 20px; border-radius: 8px; border-left: 4px solid #8b5cf6;">
            <h3 style="color: #8b5cf6; margin-bottom: 10px;">📊 Monitoreo</h3>
            <p style="color: #666;">
                Crea listeners para métricas, analytics, APM. Un solo lugar para todo el seguimiento.
            </p>
        </div>
        
        <div style="background: #f0fdfa; padding: 20px; border-radius: 8px; border-left: 4px solid #14b8a6;">
            <h3 style="color: #14b8a6; margin-bottom: 10px;">🔁 Ejecución Condicional</h3>
            <p style="color: #666;">
                Ejecuta acciones solo bajo ciertas condiciones (montos, usuarios, productos específicos).
            </p>
        </div>
    </div>
</div>

{{-- Cómo Añadir Nuevo Listener --}}
<div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
    <h2 style="margin-bottom: 20px; color: white;">➕ Cómo Añadir una Nueva Acción</h2>
    
    <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 20px; border-radius: 8px; margin: 15px 0;">
        <h3 style="margin-bottom: 15px;">Paso 1: Crear el Listener</h3>
        <div class="code-block"><pre>php artisan make:listener NombreListener --event=PaymentCompleted</pre>
        </div>
    </div>
    
    <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 20px; border-radius: 8px; margin: 15px 0;">
        <h3 style="margin-bottom: 15px;">Paso 2: Implementar la Lógica</h3>
        <div class="code-block"><pre><span class="keyword">class</span> <span class="function">NombreListener</span>
{
    <span class="keyword">public function</span> <span class="function">handle</span>(PaymentCompleted <span class="variable">$event</span>): <span class="keyword">void</span>
    {
        <span class="comment">// Tu código aquí</span>
        <span class="comment">// Acceso a datos:</span>
        <span class="comment">//   $event->provider</span>
        <span class="comment">//   $event->amount</span>
        <span class="comment">//   $event->orderId</span>
        <span class="comment">//   $event->customerEmail</span>
        <span class="comment">//   $event->metadata</span>
    }
    
    <span class="comment">// Opcional: ejecutar en cola (background)</span>
    <span class="keyword">public function</span> <span class="function">shouldQueue</span>(): <span class="keyword">bool</span>
    {
        <span class="keyword">return</span> <span class="keyword">true</span>;
    }
}</pre>
        </div>
    </div>
    
    <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 20px; border-radius: 8px; margin: 15px 0;">
        <h3 style="margin-bottom: 15px;">Paso 3: Registrar en AppServiceProvider</h3>
        <div class="code-block"><pre><span class="comment">// app/Providers/AppServiceProvider.php</span>

Event::<span class="function">listen</span>(PaymentCompleted::<span class="keyword">class</span>, [
    LogPaymentToDatabase::<span class="keyword">class</span>,
    SendPaymentConfirmationEmail::<span class="keyword">class</span>,
    SendAdminNotification::<span class="keyword">class</span>,
    UpdateInventory::<span class="keyword">class</span>,
    NombreListener::<span class="keyword">class</span>,  <span class="comment">← Tu nuevo listener</span>
]);</pre>
        </div>
    </div>
    
    <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 8px; margin-top: 20px; border: 2px solid rgba(255,255,255,0.3);">
        <strong>🎉 ¡Listo!</strong> Tu nueva acción se ejecutará automáticamente en TODOS los pagos exitosos,
        sin importar si fueron con Stripe, Redsys o PayPal.
    </div>
</div>

{{-- Ejemplo Real --}}
<div class="card">
    <h2 style="margin-bottom: 20px;">💼 Ejemplo Real de Uso</h2>
    
    <p style="color: #666; margin-bottom: 20px;">
        Imaginemos que queremos enviar una notificación a Discord cuando un pago sea mayor a €100:
    </p>
    
    <div class="code-block"><pre><span class="comment">// app/Listeners/SendDiscordHighValueAlert.php</span>

<span class="keyword">namespace</span> App\Listeners;

<span class="keyword">use</span> App\Events\PaymentCompleted;
<span class="keyword">use</span> Illuminate\Support\Facades\Http;

<span class="keyword">class</span> <span class="function">SendDiscordHighValueAlert</span>
{
    <span class="keyword">public function</span> <span class="function">handle</span>(PaymentCompleted <span class="variable">$event</span>): <span class="keyword">void</span>
    {
        <span class="comment">// Solo si el pago es >= 100€</span>
        <span class="keyword">if</span> (<span class="variable">$event</span>-><span class="variable">amount</span> < <span class="string">100</span>) {
            <span class="keyword">return</span>;
        }
        
        <span class="comment">// Construir mensaje para Discord</span>
        <span class="variable">$webhook</span> = <span class="function">config</span>(<span class="string">'services.discord.webhook'</span>);
        <span class="variable">$message</span> = <span class="string">"💰 ¡Pago grande! €{$event->amount} via {$event->provider->value}"</span>;
        
        <span class="comment">// Enviar a Discord</span>
        Http::<span class="function">post</span>(<span class="variable">$webhook</span>, [
            <span class="string">'content'</span> => <span class="variable">$message</span>,
            <span class="string">'embeds'</span> => [[
                <span class="string">'title'</span> => <span class="string">'Nuevo Pago de Alto Valor'</span>,
                <span class="string">'color'</span> => <span class="string">0x00ff00</span>,
                <span class="string">'fields'</span> => [
                    [
                        <span class="string">'name'</span>   => <span class="string">'Cantidad'</span>,
                        <span class="string">'value'</span>  => <span class="string">"€{$event->amount}"</span>,
                        <span class="string">'inline'</span> => <span class="keyword">true</span>
                    ],
                    [
                        <span class="string">'name'</span>   => <span class="string">'Proveedor'</span>,
                        <span class="string">'value'</span>  => <span class="variable">$event</span>-><span class="variable">provider</span>-><span class="variable">value</span>,
                        <span class="string">'inline'</span> => <span class="keyword">true</span>
                    ],
                    [
                        <span class="string">'name'</span>  => <span class="string">'Order ID'</span>,
                        <span class="string">'value'</span> => <span class="variable">$event</span>-><span class="variable">orderId</span>
                    ],
                ]
            ]]
        ]);
    }
    
    <span class="keyword">public function</span> <span class="function">shouldQueue</span>(): <span class="keyword">bool</span>
    {
        <span class="keyword">return</span> <span class="keyword">true</span>;  <span class="comment">// Ejecutar en background</span>
    }
}</pre>
    </div>
    
    <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin-top: 20px;">
        <strong>💡 Resultado:</strong> Cada vez que alguien pague €100 o más (con cualquier proveedor), 
        recibirás automáticamente una notificación en Discord. ¡Sin modificar ningún controller!
    </div>
</div>

{{-- Recursos --}}
<div class="card">
    <h2 style="margin-bottom: 20px;">📚 Recursos Adicionales</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
        <a href="https://laravel.com/docs/events" target="_blank" style="text-decoration: none; color: inherit;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; transition: transform 0.2s;">
                <h4 style="margin: 0 0 10px 0; color: #667eea;">📖 Laravel Events Docs</h4>
                <p style="margin: 0; color: #666; font-size: 14px;">Documentación oficial de Laravel sobre eventos</p>
            </div>
        </a>
        
        <a href="https://laravel.com/docs/queues" target="_blank" style="text-decoration: none; color: inherit;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; transition: transform 0.2s;">
                <h4 style="margin: 0 0 10px 0; color: #667eea;">⏱️ Laravel Queues Docs</h4>
                <p style="margin: 0; color: #666; font-size: 14px;">Cómo configurar colas para listeners asíncronos</p>
            </div>
        </a>
        
        <a href="{{ route('home') }}" style="text-decoration: none; color: inherit;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #4CAF50; transition: transform 0.2s;">
                <h4 style="margin: 0 0 10px 0; color: #4CAF50;">🏠 Volver al Inicio</h4>
                <p style="margin: 0; color: #666; font-size: 14px;">Probar pagos con Stripe, Redsys o PayPal</p>
            </div>
        </a>
    </div>
</div>
@endsection

