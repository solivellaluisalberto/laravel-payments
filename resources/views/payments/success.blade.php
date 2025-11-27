@extends('layouts.app')

@section('title', 'Pago Exitoso')

@section('content')
<div class="card">
    <div style="text-align: center; font-size: 72px; margin-bottom: 20px;">✅</div>
    <h1 style="text-align: center; color: #28a745; margin-bottom: 20px;">¡Pago Completado!</h1>
    
    <div style="background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin-bottom: 30px;">
        <p style="margin-bottom: 10px; color: #155724;"><strong>Proveedor:</strong> {{ $provider }}</p>
        <p style="margin-bottom: 10px; color: #155724;"><strong>ID de Transacción:</strong> {{ $result->transactionId ?? 'N/A' }}</p>
        <p style="margin-bottom: 10px; color: #155724;"><strong>Estado:</strong> {{ ucfirst($result->status) }}</p>
        @if($result->message)
            <p style="margin-bottom: 0; color: #155724;"><strong>Mensaje:</strong> {{ $result->message }}</p>
        @endif
    </div>
    
    @if(!empty($result->data))
        <details style="margin-bottom: 30px;">
            <summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">Ver detalles técnicos</summary>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px;">{{ print_r($result->data, true) }}</pre>
        </details>
    @endif
    
    <div style="text-align: center;">
        <a href="{{ route('home') }}" class="btn">← Volver al inicio</a>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">✨ ¿Qué pasó aquí?</h3>
    <ol style="line-height: 2; color: #666;">
        <li>El usuario completó el pago con {{ $provider }}</li>
        <li>{{ $provider }} procesó el pago exitosamente</li>
        <li>Recibimos la confirmación del pago</li>
        <li>Verificamos la firma y la autenticidad de la respuesta</li>
        <li>El pago está confirmado y completo ✅</li>
    </ol>
    
    <div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-radius: 8px; border-left: 4px solid #17a2b8;">
        <strong>💡 En producción:</strong> Aquí guardarías el pago en base de datos, enviarías email de confirmación, actualizarías el pedido, etc.
    </div>
</div>
@endsection

