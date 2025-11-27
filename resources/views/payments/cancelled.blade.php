@extends('layouts.app')

@section('title', 'Pago Cancelado')

@section('content')
<div class="card">
    <div style="text-align: center; font-size: 72px; margin-bottom: 20px;">⚠️</div>
    <h1 style="text-align: center; color: #ffc107; margin-bottom: 20px;">Pago Cancelado</h1>
    
    <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 30px; text-align: center;">
        <p style="color: #856404; margin: 0;">
            Has cancelado el proceso de pago con <strong>{{ $provider }}</strong>.
            No se ha realizado ningún cargo.
        </p>
    </div>
    
    <div style="text-align: center;">
        <a href="{{ route('home') }}" class="btn">← Volver al inicio</a>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">↻ Reintentar Pago</a>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 15px;">ℹ️ ¿Qué pasó?</h3>
    <p style="color: #666; line-height: 1.8;">
        Cancelaste el pago durante el proceso de autorización en {{ $provider }}.
        Esto es completamente normal y no has sido cargado.
    </p>
    
    <p style="color: #666; line-height: 1.8; margin-top: 15px;">
        Si deseas completar el pago, puedes intentarlo nuevamente haciendo clic en el botón de arriba.
    </p>
    
    <div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-radius: 8px; border-left: 4px solid #17a2b8;">
        <strong>💡 En producción:</strong> Aquí podrías registrar la cancelación, enviar un recordatorio por email, o mostrar productos alternativos.
    </div>
</div>
@endsection

