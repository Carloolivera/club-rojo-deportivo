<?php

namespace App\Filament\Widgets;

use App\Models\Fee;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;

class MonthlyFeesWidget extends Widget
{
    protected string $view = 'filament.widgets.monthly-fees-widget';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'lg' => 1,
    ];

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Gate::allows('view-reports');
    }

    public function getData(): array
    {
        $period = now()->format('Y-m');

        $counts = Fee::forPeriod($period)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = $counts->get('pending', 0);
        $paid    = $counts->get('paid', 0);
        $overdue = $counts->get('overdue', 0);
        $exempt  = $counts->get('exempt', 0);
        $total   = $counts->sum();

        $pct = fn (int $n): float => $total > 0 ? round($n / $total * 100, 1) : 0;

        return [
            'period'  => $period,
            'total'   => $total,
            'segments' => [
                ['label' => 'Pagadas',   'count' => $paid,    'pct' => $pct($paid),    'color' => '#34d399'],
                ['label' => 'Pendientes','count' => $pending,  'pct' => $pct($pending),  'color' => '#fbbf24'],
                ['label' => 'Vencidas',  'count' => $overdue,  'pct' => $pct($overdue),  'color' => '#f87171'],
                ['label' => 'Exentas',   'count' => $exempt,   'pct' => $pct($exempt),   'color' => '#94a3b8'],
            ],
        ];
    }
}
