<x-filament-widgets::widget class="fi-wi-overdue-semaphore">
    @php $data = $this->getData(); @endphp

    <div class="sem-card" style="--sem-color: {{ $data['color'] }}">
        <div class="sem-indicator">
            <div class="sem-dot-ring">
                <div class="sem-dot"></div>
            </div>
        </div>

        <div class="sem-body">
            <p class="sem-label">Alertas de cuotas vencidas</p>

            @if ($data['status'] === 'ok')
                <p class="sem-value sem-ok">Sin alertas</p>
            @else
                <p class="sem-value">{{ $data['count'] }}</p>
            @endif

            <p class="sem-message">{{ $data['message'] }}</p>
        </div>

        <div class="sem-status-chip sem-status-{{ $data['status'] }}">
            @if ($data['status'] === 'ok')
                <svg class="sem-chip-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                OK
            @elseif ($data['status'] === 'warning')
                <svg class="sem-chip-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                Atención
            @else
                <svg class="sem-chip-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                Alerta
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
