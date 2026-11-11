<x-filament-widgets::widget class="fi-wi-monthly-fees">
    @php $data = $this->getData(); @endphp

    <div class="mf-card">
        <div class="mf-header">
            <div>
                <p class="mf-title">Cuotas {{ $data['period'] }}</p>
                <p class="mf-subtitle">{{ $data['total'] }} cuotas totales este mes</p>
            </div>
        </div>

        <div class="mf-bar-wrap">
            <div class="mf-bar">
                @foreach ($data['segments'] as $seg)
                    @if ($seg['pct'] > 0)
                        <div
                            class="mf-bar-segment"
                            style="width: {{ $seg['pct'] }}%; background: {{ $seg['color'] }};"
                            title="{{ $seg['label'] }}: {{ $seg['count'] }}"
                        ></div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="mf-legend">
            @foreach ($data['segments'] as $seg)
                <div class="mf-legend-item">
                    <span class="mf-dot" style="background: {{ $seg['color'] }};"></span>
                    <span class="mf-legend-label">{{ $seg['label'] }}</span>
                    <span class="mf-legend-count">{{ $seg['count'] }}</span>
                    <span class="mf-legend-pct">{{ $seg['pct'] }}%</span>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
