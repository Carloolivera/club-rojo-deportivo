<x-filament-widgets::widget class="fi-wi-revenue-evolution">
    @php
        $data = $this->getData();
        $chartId = 'rev-chart-' . $this->getId();
    @endphp

    <div class="mf-card">
        <div class="mf-header">
            <div>
                <p class="mf-title">Evolución mensual — últimos 6 meses</p>
                <p class="mf-subtitle">Recaudación cobrada y nuevos socios por mes</p>
            </div>
        </div>

        <div style="position: relative; height: 260px; padding: 0 4px;">
            <canvas id="{{ $chartId }}"></canvas>
        </div>
    </div>

    @script
    <script>
        (function () {
            const labels   = @json($data['labels']);
            const revenues = @json($data['revenues']);
            const members  = @json($data['members']);

            function renderChart() {
                const canvas = document.getElementById('{{ $chartId }}');
                if (!canvas) return;

                const existing = Chart.getChart(canvas);
                if (existing) existing.destroy();

                new Chart(canvas, {
                    data: {
                        labels,
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Recaudación ($)',
                                data: revenues,
                                backgroundColor: 'rgba(215, 31, 40, 0.7)',
                                borderColor: 'rgba(215, 31, 40, 1)',
                                borderWidth: 1,
                                yAxisID: 'yRevenue',
                                borderRadius: 4,
                            },
                            {
                                type: 'line',
                                label: 'Nuevos socios',
                                data: members,
                                borderColor: '#34d399',
                                backgroundColor: 'rgba(52, 211, 153, 0.15)',
                                pointBackgroundColor: '#34d399',
                                pointRadius: 4,
                                tension: 0.35,
                                fill: true,
                                yAxisID: 'yMembers',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                labels: { color: '#94a3b8', font: { size: 12 } },
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        if (ctx.dataset.yAxisID === 'yRevenue') {
                                            return ' $' + ctx.parsed.y.toLocaleString('es-AR');
                                        }
                                        return ' ' + ctx.parsed.y + ' socios';
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                ticks: { color: '#64748b' },
                                grid:  { color: 'rgba(100,116,139,0.1)' },
                            },
                            yRevenue: {
                                position: 'left',
                                ticks: {
                                    color: '#94a3b8',
                                    callback: v => '$' + v.toLocaleString('es-AR'),
                                },
                                grid: { color: 'rgba(100,116,139,0.1)' },
                            },
                            yMembers: {
                                position: 'right',
                                ticks: { color: '#34d399' },
                                grid: { drawOnChartArea: false },
                            },
                        },
                    },
                });
            }

            if (typeof Chart !== 'undefined') {
                renderChart();
            } else {
                document.addEventListener('chartjs:loaded', renderChart, { once: true });
            }
        })();
    </script>
    @endscript
</x-filament-widgets::widget>
