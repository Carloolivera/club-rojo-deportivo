{{-- resources/views/member/fees-payment.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado del pago — Club Rojo Deportivo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center justify-center px-4 gap-4">

    @php $status = request('status', 'pending') @endphp

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 max-w-sm w-full text-center">

        @if ($status === 'success')
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">¡Pago recibido!</h2>
            <p class="text-gray-500 text-sm mt-2">Tu cuota será actualizada en breve. Guardá el comprobante de MercadoPago como respaldo.</p>

        @elseif ($status === 'failure')
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Pago no procesado</h2>
            <p class="text-gray-500 text-sm mt-2">No se realizó ningún cobro. Podés intentar de nuevo desde el link que recibiste.</p>

        @else
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Pago en proceso</h2>
            <p class="text-gray-500 text-sm mt-2">Estamos verificando tu pago con MercadoPago. Puede demorar unos minutos en reflejarse.</p>
        @endif

        <p class="text-xs text-gray-400 mt-5 border-t border-gray-100 pt-4">
            ¿Dudas? Contactá a la secretaría del club.
        </p>
    </div>

    <a href="{{ route('portal.estado') }}" class="text-xs text-gray-400 hover:text-gray-600 transition">
        ← Volver a la consulta de estado
    </a>

</body>
</html>
