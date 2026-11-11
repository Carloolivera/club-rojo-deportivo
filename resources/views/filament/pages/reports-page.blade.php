<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Filtro de período --}}
        <div class="flex items-center gap-4">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="month"
                    wire:model.live="selectedPeriod"
                    class="w-48"
                />
            </x-filament::input.wrapper>
        </div>

        {{-- KPIs del período --}}
        <div style="display:flex; flex-wrap:wrap; gap:0; border:1px solid var(--club-border,#e2e8f0); border-radius:12px; overflow:hidden; background:var(--club-card,#fff);">
            @php
                $kpis = [
                    ['icon'=>'heroicon-o-user-group',                  'label'=>'Socios activos',     'value'=>$activeMembers,                                 'color'=>'#34d399'],
                    ['icon'=>'heroicon-o-user-minus',                  'label'=>'Socios inactivos',   'value'=>$inactiveMembers,                               'color'=>'#94a3b8'],
                    ['icon'=>'heroicon-o-arrow-left-end-on-rectangle', 'label'=>'Bajas del mes',      'value'=>$leavesCount,                                   'color'=>'#fbbf24'],
                    ['icon'=>'heroicon-o-banknotes',                   'label'=>'Ingresos del período','value'=>'$ '.number_format($income,0,',','.'),          'color'=>'#D71F28'],
                ];
            @endphp
            @foreach ($kpis as $i => $kpi)
                <div style="flex:1; min-width:160px; display:flex; align-items:center; gap:10px; padding:14px 18px;
                            {{ $i < count($kpis)-1 ? 'border-right:1px solid var(--club-border,#e2e8f0);' : '' }}">
                    <x-filament::icon :icon="$kpi['icon']" style="width:20px;height:20px;flex-shrink:0;color:{{ $kpi['color'] }};" />
                    <div style="min-width:0;">
                        <p style="font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0;white-space:nowrap;">{{ $kpi['label'] }}</p>
                        <p style="font-size:1.15rem;font-weight:700;color:var(--club-text,#1e293b);margin:0;line-height:1.2;">{{ $kpi['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Acciones de exportación --}}
        <div class="space-y-2">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Exportar</p>
            <div class="flex flex-wrap gap-3">

                <x-filament::button
                    wire:click="exportExcel"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Excel — Nómina de socios
                </x-filament::button>

                <x-filament::button
                    wire:click="exportFeesExcel"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Excel — Cuotas {{ $selectedPeriod }}
                </x-filament::button>

                <x-filament::button
                    wire:click="exportOverdueExcel"
                    icon="heroicon-o-table-cells"
                    color="warning"
                >
                    Excel — Morosos
                </x-filament::button>

                <x-filament::button
                    wire:click="exportMembersPdf"
                    icon="heroicon-o-document-arrow-down"
                    color="gray"
                >
                    PDF — Nómina de socios
                </x-filament::button>

                <x-filament::button
                    wire:click="exportFeesPdf"
                    icon="heroicon-o-document-arrow-down"
                    color="info"
                >
                    PDF — Cuotas {{ $selectedPeriod }}
                </x-filament::button>

                <x-filament::button
                    wire:click="exportOverduePdf"
                    icon="heroicon-o-exclamation-triangle"
                    color="danger"
                >
                    PDF — Listado de morosos
                </x-filament::button>

            </div>
        </div>

    </div>
</x-filament-panels::page>
