{{-- resources/views/member/fees.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cuotas — Club Rojo Deportivo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="max-w-2xl mx-auto px-4 py-8">

        {{-- Header --}}
        <div class="flex items-center gap-4 mb-6">
            <img src="/icons/escudo.svg" alt="Club Rojo Deportivo" class="w-12 h-14 object-contain flex-shrink-0">
            <div>
                <h1 class="text-lg font-bold text-gray-800 leading-tight">Club Rojo Deportivo</h1>
                <p class="text-gray-400 text-xs">Portal de socios</p>
            </div>
        </div>

        {{-- Tarjeta resumen del socio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Socio</p>
                    <p class="text-lg font-bold text-gray-800 truncate">{{ $member->name }}</p>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                        @if ($member->sport)
                            <span class="text-sm text-gray-500">
                                <span class="font-medium text-gray-700">Deporte:</span> {{ $member->sport->name }}
                            </span>
                        @endif
                        @if ($member->feeCategory)
                            <span class="text-sm text-gray-500">
                                <span class="font-medium text-gray-700">Categoría:</span> {{ $member->feeCategory->name }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex-shrink-0">
                    @if ($member->status === 'active')
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-3 py-1.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Activo
                        </span>
                    @elseif ($member->status === 'suspended')
                        <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-700 text-xs font-semibold px-3 py-1.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Suspendido
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 text-xs font-semibold px-3 py-1.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactivo
                        </span>
                    @endif
                </div>
            </div>

            {{-- Resumen de deuda --}}
            @php
                $overdueCount  = $fees->where('status', 'overdue')->count();
                $overdueAmount = $fees->where('status', 'overdue')->sum('amount');
                $pendingCount  = $fees->where('status', 'pending')->count();
            @endphp

            @if ($overdueCount > 0)
                <div class="mt-4 flex items-center justify-between bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                    <div>
                        <p class="text-xs text-red-500 font-medium">Cuotas vencidas</p>
                        <p class="text-sm font-bold text-red-700 mt-0.5">
                            {{ $overdueCount }} {{ $overdueCount === 1 ? 'cuota' : 'cuotas' }} — ${{ number_format($overdueAmount, 2, ',', '.') }}
                        </p>
                    </div>
                    <span class="text-red-400 text-xl">⚠</span>
                </div>
            @elseif ($pendingCount === 0 && $fees->isNotEmpty())
                <div class="mt-4 flex items-center gap-3 bg-green-50 border border-green-100 rounded-xl px-4 py-3">
                    <span class="text-green-500 text-xl">✔</span>
                    <p class="text-sm font-semibold text-green-700">¡Estás al día con todas tus cuotas!</p>
                </div>
            @endif
        </div>

        {{-- Lista de cuotas --}}
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Historial de cuotas</p>

        @forelse ($fees as $fee)
            <div class="bg-white rounded-xl shadow-sm border-l-4 mb-3
                {{ $fee->status === 'paid'    ? 'border-green-400' :
                   ($fee->status === 'overdue' ? 'border-red-400'   : 'border-amber-400') }}">
                <div class="flex justify-between items-center px-5 py-4">
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Cuota {{ $fee->period }}</p>
                        <p class="text-gray-500 text-xs mt-0.5">${{ number_format($fee->amount, 2, ',', '.') }}</p>
                    </div>
                    <div class="text-right flex flex-col items-end gap-2">
                        @if ($fee->status === 'paid')
                            <span class="inline-block bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">Pagada</span>
                            <p class="text-gray-400 text-xs">{{ $fee->paid_at?->format('d/m/Y') }}</p>
                        @elseif ($fee->status === 'overdue')
                            <span class="inline-block bg-red-100 text-red-700 text-xs font-semibold px-3 py-1 rounded-full">Vencida</span>
                            <a href="{{ URL::signedRoute('member.fees.pay', ['fee' => $fee->id]) }}"
                               class="inline-block bg-[#1e3a5f] hover:bg-[#162d4a] text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                Pagar con MercadoPago
                            </a>
                        @else
                            <span class="inline-block bg-amber-100 text-amber-700 text-xs font-semibold px-3 py-1 rounded-full">Próxima</span>
                            <a href="{{ URL::signedRoute('member.fees.pay', ['fee' => $fee->id]) }}"
                               class="inline-block border border-[#1e3a5f] text-[#1e3a5f] hover:bg-[#1e3a5f] hover:text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                Pagar anticipado
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm">
                No hay cuotas registradas aún.
            </div>
        @endforelse

        {{-- Footer --}}
        <div class="mt-8 text-center">
            <a href="{{ route('portal.estado') }}" class="text-xs text-gray-400 hover:text-gray-600 transition">
                ← Volver a la consulta de estado
            </a>
        </div>

    </div>
</body>
</html>
