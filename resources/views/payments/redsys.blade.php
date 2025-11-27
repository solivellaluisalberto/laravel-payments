@extends('layouts.app')

@section('title', 'Ejemplo Redsys')

@section('content')
<div class="header">
    <h1>🏦 Pago con Redsys</h1>
    <p>Ejemplo de integración con Redsys TPV</p>
</div>

@if(session('error'))
    <div class="alert alert-error">
        ❌ {{ session('error') }}
    </div>
@endif

<div class="card">
    <h2 style="margin-bottom: 20px;">Información del Pago</h2>
    
    <form action="{{ route('payments.redsys.initiate') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="amount">Cantidad (EUR)</label>
            <input type="number" id="amount" name="amount" value="50.00" step="0.01" min="0.50" required>
        </div>
        
        <div class="form-group">
            <label for="payment_method">Método de Pago</label>
            <select id="payment_method" name="payment_method" required>
                <option value="card">Tarjeta</option>
                <option value="bizum">Bizum</option>
            </select>
        </div>
        
        <button type="submit" class="btn">Continuar al TPV →</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">💡 Datos de Prueba (Entorno Test)</h3>
    <ul style="line-height: 2; color: #666;">
        <li><strong>Tarjeta:</strong> 4548 8120 4940 0004</li>
        <li><strong>Caducidad:</strong> 12/25 (o cualquier fecha futura)</li>
        <li><strong>CVV:</strong> 123</li>
        <li><strong>CIP:</strong> 123456</li>
    </ul>
    
    <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
        <strong>⚠️ Nota:</strong> Redsys te redirigirá a su TPV (Terminal Punto de Venta). Después del pago, volverás automáticamente a esta aplicación.
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">🔄 Flujo del Pago</h3>
    <ol style="line-height: 2; color: #666;">
        <li>Introduces la cantidad y método de pago</li>
        <li>Se genera un formulario firmado con la clave secreta</li>
        <li>Eres redirigido al TPV de Redsys</li>
        <li>Introduces los datos de tu tarjeta en el TPV</li>
        <li>Redsys procesa el pago</li>
        <li>Redsys te redirige de vuelta con el resultado</li>
    </ol>
</div>
@endsection

