<?php

namespace App\Filament\Widgets;

use App\Models\Fee;
use App\Models\Member;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class KpiStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.kpi-stats-widget';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Gate::allows('view-reports');
    }

    public function getData(): array
    {
        $period = now()->format('Y-m');

        [$active, $inactive, $income] = Cache::remember(
            "kpi_stats_{$period}",
            now()->addMinutes(10),
            function () use ($period) {
                return [
                    Member::where('status', 'active')->count(),
                    Member::where('status', 'inactive')->count(),
                    Fee::where('status', 'paid')
                        ->where('period', $period)
                        ->sum('amount'),
                ];
            }
        );

        return [
            [
                'label'    => 'Socios activos',
                'value'    => $active,
                'icon'     => 'heroicon-o-user-group',
                'color'    => '#D71F28',
                'subtext'  => 'al día',
            ],
            [
                'label'    => 'Socios inactivos',
                'value'    => $inactive,
                'icon'     => 'heroicon-o-user-minus',
                'color'    => 'rgba(255,255,255,0.35)',
                'subtext'  => 'sin actividad',
            ],
            [
                'label'    => 'Ingresos ' . $period,
                'value'    => '$' . number_format($income, 0, ',', '.'),
                'icon'     => 'heroicon-o-banknotes',
                'color'    => '#34d399',
                'subtext'  => 'cuotas cobradas',
            ],
        ];
    }
}
