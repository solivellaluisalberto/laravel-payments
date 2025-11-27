@extends('layouts.app')

@section('title', 'Ejemplo Stripe')

@push('styles')
<style>
    #card-element {
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
    }
    
    #card-errors {
        color: #dc3545;
        margin-top: 10px;
        font-size: 14px;
    }
    
    #payment-message {
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
    
    #payment-message.success {
        display: block;
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border: 3px solid #28a745;
        box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
    }
    
    #payment-message.error {
        display: block;
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
        border: 3px solid #dc3545;
        box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
    }
    
    #payment-message .icon {
        font-size: 48px;
        margin-bottom: 10px;
        display: block;
    }
    
    #payment-message .details {
        font-size: 14px;
        font-weight: normal;
        margin-top: 10px;
        opacity: 0.8;
    }
</style>
@endpush

@section('content')
<div class="header">
    <h1>💳 Pago con Stripe</h1>
    <p>Ejemplo de integración con Stripe Payment Intents</p>
</div>

<div class="card">
    <h2 style="margin-bottom: 20px;">Información del Pago</h2>
    
    <form id="payment-form">
        @csrf
        
        <div class="form-group">
            <label for="amount">Cantidad (EUR)</label>
            <input type="number" id="amount" name="amount" value="50.00" step="0.01" min="0.50" required>
        </div>
        
        <div class="form-group">
            <label for="card-element">Tarjeta de Crédito</label>
            <div id="card-element"></div>
            <div id="card-errors" role="alert"></div>
        </div>
        
        <button type="submit" id="submit-button" class="btn">
            <span id="button-text">Pagar</span>
            <span id="spinner" style="display: none;">⏳ Procesando...</span>
        </button>
    </form>
    
    <div id="payment-message"></div>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">💡 Tarjetas de Prueba</h3>
    <ul style="line-height: 2; color: #666;">
        <li><strong>4242 4242 4242 4242</strong> → Pago exitoso</li>
        <li><strong>4000 0000 0000 0002</strong> → Tarjeta rechazada</li>
        <li><strong>4000 0000 0000 9995</strong> → Fondos insuficientes</li>
        <li>Fecha: cualquier fecha futura</li>
        <li>CVC: cualquier 3 dígitos</li>
    </ul>
</div>
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    // Inicializar Stripe
    const stripe = Stripe('{{ $publicKey }}');
    const elements = stripe.elements();
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
        },
    });
    
    cardElement.mount('#card-element');
    
    // Manejar errores de validación
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Manejar el envío del formulario
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');
    const paymentMessage = document.getElementById('payment-message');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Deshabilitar botón
        submitButton.disabled = true;
        buttonText.style.display = 'none';
        spinner.style.display = 'inline';
        paymentMessage.className = ''; // Limpiar clases
        paymentMessage.innerHTML = ''; // Limpiar contenido
        
        try {
            // Paso 1: Crear Payment Intent
            const response = await fetch('{{ route('payments.stripe.initiate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    amount: document.getElementById('amount').value
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error creating payment');
            }
            
            // Paso 2: Confirmar el pago con Stripe
            const {error} = await stripe.confirmCardPayment(data.clientSecret, {
                payment_method: {
                    card: cardElement,
                }
            });
            
            if (error) {
                // Error del pago
                paymentMessage.innerHTML = `
                    <span class="icon">❌</span>
                    <div>Error en el Pago</div>
                    <div class="details">${error.message}</div>
                `;
                paymentMessage.className = 'error';
                
                // Scroll al mensaje
                paymentMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                // Pago exitoso - obtener el payment intent ID
                const amount = document.getElementById('amount').value;
                
                // Obtener el payment intent ID del resultado
                const paymentIntentId = data.data.payment_intent_id || 'N/A';
                
                paymentMessage.innerHTML = `
                    <span class="icon">✅</span>
                    <div>¡Pago Completado con Éxito!</div>
                    <div class="details">
                        Cantidad: €${parseFloat(amount).toFixed(2)}<br>
                        Payment Intent ID: <strong style="color: #155724; user-select: all;">${paymentIntentId}</strong><br>
                        <small style="opacity: 0.7;">💡 Guarda este ID para hacer reembolsos</small>
                    </div>
                `;
                paymentMessage.className = 'success';
                
                // Scroll al mensaje
                paymentMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // NO resetear el formulario para que vean el ID
                // El usuario puede hacer click en "Volver al inicio" cuando quiera
                
                // Opcional: Reproducir sonido de éxito
                try {
                    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBzWP1PLNfC0GKnzM8+GUQgsVYbjp7KZUEwlMov==');
                    audio.play().catch(() => {}); // Ignorar si falla
                } catch (e) {}
            }
        } catch (error) {
            paymentMessage.innerHTML = `
                <span class="icon">⚠️</span>
                <div>Error Inesperado</div>
                <div class="details">${error.message}</div>
            `;
            paymentMessage.className = 'error';
            
            // Scroll al mensaje
            paymentMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } finally {
            // Rehabilitar botón
            submitButton.disabled = false;
            buttonText.style.display = 'inline';
            spinner.style.display = 'none';
        }
    });
</script>
@endpush

