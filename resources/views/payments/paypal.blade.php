@extends('layouts.app')

@section('title', 'Ejemplo PayPal')

@section('content')
<div class="header">
    <h1>💰 Pago con PayPal</h1>
    <p>Ejemplo de integración con PayPal Checkout SDK</p>
</div>

@if(session('error'))
    <div class="alert alert-error">
        ❌ {{ session('error') }}
    </div>
@endif

<div class="card">
    <h2 style="margin-bottom: 20px;">Información del Pago</h2>
    
    <form action="{{ route('payments.paypal.initiate') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="amount">Cantidad (EUR)</label>
            <input type="number" id="amount" name="amount" value="50.00" step="0.01" min="0.50" required>
        </div>
        
        <button type="submit" class="btn">Pagar con PayPal →</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">💡 Cuenta de Prueba</h3>
    <div style="padding: 15px; background: #d1ecf1; border-radius: 8px; border-left: 4px solid #17a2b8;">
        <p style="margin-bottom: 10px;"><strong>Para usar PayPal en sandbox:</strong></p>
        <ol style="line-height: 2; color: #0c5460;">
            <li>Crea una cuenta de prueba en <a href="https://developer.paypal.com/dashboard/accounts" target="_blank" style="color: #0c5460; text-decoration: underline;">PayPal Developer Dashboard</a></li>
            <li>Usa las credenciales de la cuenta personal de prueba</li>
            <li>O usa la cuenta de prueba que PayPal te proporciona automáticamente</li>
        </ol>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">🔄 Flujo del Pago</h3>
    <ol style="line-height: 2; color: #666;">
        <li>Introduces la cantidad a pagar</li>
        <li>Se crea una orden en PayPal</li>
        <li>Eres redirigido a PayPal</li>
        <li>Inicias sesión y apruebas el pago en PayPal</li>
        <li>PayPal te redirige de vuelta</li>
        <li>Capturamos el pago y mostramos el resultado</li>
    </ol>
</div>
@endsection

