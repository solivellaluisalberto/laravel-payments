@extends('layouts.app')

@section('title', 'Ejemplo de Reembolsos')

@push('styles')
<style>
    #refund-message {
        padding: 20px;
        border-radius: 12px;
        margin-top: 20px;
        display: none;
        font-size: 18px;
        font-weight: 600;
        text-align: center;
        animation: slideDown 0.5s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    #refund-message.success {
        display: block;
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border: 3px solid #28a745;
        box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
    }
    
    #refund-message.error {
        display: block;
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
        border: 3px solid #dc3545;
        box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
    }
    
    #refund-message .icon {
        font-size: 48px;
        margin-bottom: 10px;
        display: block;
    }
    
    #refund-message .details {
        font-size: 14px;
        font-weight: normal;
        margin-top: 10px;
        opacity: 0.8;
    }
</style>
@endpush

@section('content')
<div class="header">
    <h1>↩️ Reembolsos</h1>
    <p>Ejemplos de devolución de pagos con Stripe, Redsys y PayPal</p>
</div>

<div class="card">
    <h2 style="margin-bottom: 20px;">Procesar Reembolso</h2>
    
    <form id="refund-form">
        @csrf
        
        <div class="form-group">
            <label for="provider">Proveedor</label>
            <select id="provider" name="provider" required>
                <option value="">Selecciona un proveedor</option>
                <option value="stripe">Stripe</option>
                <option value="redsys">Redsys</option>
                <option value="paypal">PayPal</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="payment_id">ID del Pago</label>
            <input type="text" id="payment_id" name="payment_id" placeholder="pi_xxx (Stripe) o ID de pedido" required>
            <small style="color: #666; display: block; margin-top: 8px; line-height: 1.6;">
                📌 <strong>Stripe:</strong> Payment Intent ID (empieza con <code>pi_</code>) o Charge ID (<code>ch_</code>)<br>
                📌 <strong>Redsys:</strong> Número de pedido (12 dígitos)<br>
                📌 <strong>PayPal:</strong> Order ID de PayPal<br>
                <span style="color: #28a745;">💡 Consejo: Copia el ID después de completar un pago de prueba</span>
            </small>
        </div>
        
        <div class="form-group">
            <label for="amount">Cantidad a Reembolsar (EUR)</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="Dejar vacío para reembolso total">
            <small style="color: #666; display: block; margin-top: 5px;">
                Si dejas este campo vacío, se reembolsará el total del pago
            </small>
        </div>
        
        <button type="submit" id="submit-button" class="btn btn-danger">
            <span id="button-text">Procesar Reembolso</span>
            <span id="spinner" style="display: none;">⏳ Procesando...</span>
        </button>
    </form>
    
    <div id="refund-message"></div>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">💡 Cómo Funcionan los Reembolsos</h3>
    
    <div style="margin-bottom: 20px;">
        <h4 style="margin-bottom: 10px; color: #667eea;">Stripe</h4>
        <p style="color: #666; line-height: 1.8;">
            Los reembolsos en Stripe son instantáneos y automáticos. 
            El dinero vuelve a la tarjeta del cliente en 5-10 días laborables 
            (dependiendo del banco).
        </p>
        <ul style="color: #666; line-height: 2; margin-top: 10px;">
            <li>✅ Reembolsos parciales soportados</li>
            <li>✅ Reembolsos totales soportados</li>
            <li>✅ API REST simple</li>
            <li>⏱️ Proceso instantáneo</li>
        </ul>
    </div>
    
    <div style="margin-bottom: 20px;">
        <h4 style="margin-bottom: 10px; color: #667eea;">Redsys</h4>
        <p style="color: #666; line-height: 1.8;">
            Los reembolsos en Redsys se procesan vía API REST. 
            Requieren el número de pedido original y la cantidad.
        </p>
        <ul style="color: #666; line-height: 2; margin-top: 10px;">
            <li>✅ Reembolsos parciales soportados</li>
            <li>✅ Reembolsos totales soportados</li>
            <li>✅ API REST (tipo de transacción '3')</li>
            <li>⏱️ Proceso puede tardar unos días</li>
        </ul>
    </div>
    
    <div>
        <h4 style="margin-bottom: 10px; color: #667eea;">PayPal</h4>
        <p style="color: #666; line-height: 1.8;">
            Los reembolsos en PayPal se procesan a través de su API. 
            Requieren el ID del capture (no el ID de la orden).
        </p>
        <ul style="color: #666; line-height: 2; margin-top: 10px;">
            <li>✅ Reembolsos parciales soportados</li>
            <li>✅ Reembolsos totales soportados</li>
            <li>✅ SDK oficial</li>
            <li>⏱️ Proceso rápido</li>
        </ul>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">🔍 Cómo Obtener el ID del Pago</h3>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
        <h4 style="margin-bottom: 10px; color: #667eea;">Stripe</h4>
        <ol style="color: #666; line-height: 2;">
            <li>Ve a <a href="{{ route('payments.stripe.example') }}" target="_blank">ejemplo de Stripe</a></li>
            <li>Completa un pago de prueba</li>
            <li>Copia el <strong>Payment Intent ID</strong> que aparece después del pago (empieza con <code>pi_</code>)</li>
            <li>Pégalo en el campo "ID del Pago" arriba</li>
        </ol>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
        <h4 style="margin-bottom: 10px; color: #667eea;">Redsys</h4>
        <ol style="color: #666; line-height: 2;">
            <li>Ve a <a href="{{ route('payments.redsys.example') }}" target="_blank">ejemplo de Redsys</a></li>
            <li>Completa un pago de prueba</li>
            <li>El número de pedido es el que usaste (12 dígitos)</li>
            <li>Ejemplo: <code>202401231234</code></li>
        </ol>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
        <h4 style="margin-bottom: 10px; color: #667eea;">PayPal</h4>
        <ol style="color: #666; line-height: 2;">
            <li>Ve a <a href="{{ route('payments.paypal.example') }}" target="_blank">ejemplo de PayPal</a></li>
            <li>Completa un pago de prueba</li>
            <li>El Order ID de PayPal aparecerá en la página de éxito</li>
            <li>Empieza con números y letras (ej: <code>8XV28914X...</code>)</li>
        </ol>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">⚠️ Importante</h3>
    <ul style="line-height: 2; color: #666;">
        <li>Los reembolsos NO se pueden cancelar una vez procesados</li>
        <li>El dinero tarda entre 5-10 días en aparecer en la cuenta del cliente</li>
        <li>Algunos bancos pueden cobrar comisiones por reembolsos</li>
        <li>En producción, deberías validar los permisos del usuario antes de permitir reembolsos</li>
        <li>Es recomendable registrar todos los reembolsos en tu base de datos</li>
    </ul>
</div>
@endsection

@push('scripts')
<script>
    const form = document.getElementById('refund-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');
    const refundMessage = document.getElementById('refund-message');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Deshabilitar botón
        submitButton.disabled = true;
        buttonText.style.display = 'none';
        spinner.style.display = 'inline';
        refundMessage.className = ''; // Limpiar clases
        refundMessage.innerHTML = ''; // Limpiar contenido
        
        try {
            const formData = {
                provider: document.getElementById('provider').value,
                payment_id: document.getElementById('payment_id').value,
                amount: document.getElementById('amount').value || null
            };
            
            const response = await fetch('{{ route('payments.refund.process') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                const amount = formData.amount || 'Total';
                const amountText = amount !== 'Total' ? `€${parseFloat(amount).toFixed(2)}` : amount;
                
                refundMessage.innerHTML = `
                    <span class="icon">✅</span>
                    <div>Reembolso Procesado</div>
                    <div class="details">
                        Proveedor: ${formData.provider.toUpperCase()}<br>
                        Cantidad: ${amountText}<br>
                        ${data.message || 'El reembolso se ha procesado correctamente'}
                    </div>
                `;
                refundMessage.className = 'success';
                
                // Scroll al mensaje
                refundMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Resetear formulario
                setTimeout(() => form.reset(), 2000);
            } else {
                refundMessage.innerHTML = `
                    <span class="icon">❌</span>
                    <div>Error en el Reembolso</div>
                    <div class="details">${data.message || 'Error procesando reembolso'}</div>
                `;
                refundMessage.className = 'error';
                
                // Scroll al mensaje
                refundMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (error) {
            refundMessage.innerHTML = `
                <span class="icon">⚠️</span>
                <div>Error Inesperado</div>
                <div class="details">${error.message}</div>
            `;
            refundMessage.className = 'error';
            
            // Scroll al mensaje
            refundMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } finally {
            submitButton.disabled = false;
            buttonText.style.display = 'inline';
            spinner.style.display = 'none';
        }
    });
</script>
@endpush

