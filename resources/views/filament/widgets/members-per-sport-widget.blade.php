<x-filament-widgets::widget class="fi-wi-members-per-sport">
    @php $data = $this->getData(); @endphp

    <div class="ms-card">
        <div class="ms-header">
            <p class="ms-title">Socios por deporte</p>
            <span class="ms-total-badge">{{ $data['total'] }} activos</span>
        </div>

        @if (empty($data['sports']))
            <p class="ms-empty">Sin datos de deportes.</p>
        @else
            <div class="ms-grid">
                @foreach ($data['sports'] as $sport)
                    <div class="ms-sport-row">
                        <p class="ms-sport-name">{{ $sport['name'] }}</p>
                        <div class="ms-bar-wrap">
                            <div class="ms-bar" style="width: {{ $sport['pct'] }}%"></div>
                        </div>
                        <span class="ms-count">{{ $sport['count'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
