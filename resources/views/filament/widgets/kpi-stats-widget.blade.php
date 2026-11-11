<x-filament-widgets::widget class="fi-wi-kpi-stats">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($this->getData() as $stat)
            <div class="kpi-card" style="--kpi-color: {{ $stat['color'] }}">
                <div class="kpi-icon-wrap">
                    <x-filament::icon
                        :icon="$stat['icon']"
                        class="kpi-icon"
                    />
                </div>
                <div class="kpi-body">
                    <p class="kpi-label">{{ $stat['label'] }}</p>
                    <p class="kpi-value">{{ $stat['value'] }}</p>
                    <p class="kpi-subtext">{{ $stat['subtext'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
