<?php

namespace App\Filament\Widgets;

use App\Models\Fee;
use App\Models\Member;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;

class RevenueEvolutionWidget extends Widget
{
    protected string $view = 'filament.widgets.revenue-evolution-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Gate::allows('view-reports');
    }

    public function getData(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        $revenue = Fee::whereIn('period', $months)
            ->where('status', 'paid')
            ->selectRaw('period, SUM(amount) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $newMembers = Member::whereBetween('created_at', [
                now()->subMonths(5)->startOfMonth(),
                now()->endOfMonth(),
            ])
            ->get()
            ->groupBy(fn ($m) => $m->created_at->format('Y-m'))
            ->map->count();

        $labels   = $months->map(fn ($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('M Y'))->values()->toArray();
        $revenues = $months->map(fn ($m) => (float) ($revenue->get($m, 0)))->values()->toArray();
        $members  = $months->map(fn ($m) => (int) ($newMembers->get($m, 0)))->values()->toArray();

        return compact('labels', 'revenues', 'members');
    }
}
