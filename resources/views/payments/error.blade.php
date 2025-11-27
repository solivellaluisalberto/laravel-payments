@extends('layouts.app')

@section('title', 'Error en el Pago')

@section('content')
<div class="card">
    <div style="text-align: center; font-size: 72px; margin-bottom: 20px;">❌</div>
    <h1 style="text-align: center; color: #dc3545; margin-bottom: 20px;">Error en el Pago</h1>
    
    <div style="background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; margin-bottom: 30px;">
        <p style="margin-bottom: 10px; color: #721c24;"><strong>Proveedor:</strong> {{ $provider }}</p>
        
        @if(isset($result))
            <p style="margin-bottom: 10px; color: #721c24;"><strong>Estado:</strong> {{ ucfirst($result->status ?? 'failed') }}</p>
            @if($result->message)
                <p style="margin-bottom: 0; color: #721c24;"><strong>Mensaje:</strong> {{ $result->message }}</p>
            @endif
        @endif
        
        @if(isset($error))
            <p style="margin-bottom: 0; color: #721c24;"><strong>Error:</strong> {{ $error }}</p>
        @endif
    </div>
    
    @if(isset($result) && !empty($result->data))
        <details style="margin-bottom: 30px;">
            <summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">Ver detalles técnicos</summary>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px;">{{ print_r($result->data, true) }}</pre>
        </details>
    @endif
    
    <div style="text-align: center;">
        <a href="{{ route('home') }}" class="btn">← Volver al inicio</a>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">↻ Reintentar</a>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">❓ Posibles Causas</h3>
    <ul style="line-height: 2; color: #666;">
        <li>Tarjeta rechazada o sin fondos</li>
        <li>Error en los datos introducidos</li>
        <li>Problema de conexión con {{ $provider }}</li>
        <li>Firma inválida o manipulación de datos</li>
        <li>Límite de transacciones alcanzado</li>
    </ul>
    
    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
        <strong>💡 En producción:</strong> Aquí registrarías el error, notificarías al equipo, y mostrarías un mensaje más amigable al usuario.
    </div>
</div>
@endsection

