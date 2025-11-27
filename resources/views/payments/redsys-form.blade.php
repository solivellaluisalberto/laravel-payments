<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo a Redsys...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .spinner {
            font-size: 48px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        h2 {
            margin: 20px 0 10px;
            color: #333;
        }
        
        p {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner">⏳</div>
        <h2>Redirigiendo al TPV de Redsys...</h2>
        <p>Por favor, espera un momento.</p>
    </div>
    
    {{-- Formulario de Redsys (se envía automáticamente) --}}
    {!! $formHtml !!}
    
    <script>
        // Auto-submit del formulario de Redsys
        document.addEventListener('DOMContentLoaded', function() {
            document.forms[0].submit();
        });
    </script>
</body>
</html>

