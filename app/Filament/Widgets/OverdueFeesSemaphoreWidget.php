<?php

namespace App\Filament\Widgets;

use App\Models\Fee;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OverdueFeesSemaphoreWidget extends Widget
{
    protected string $view = 'filament.widgets.overdue-fees-semaphore-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'lg' => 1,
    ];

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['admin', 'manager']);
    }

    public function getData(): array
    {
        $threshold   = config('alerts.days_threshold');
        $cutoffPeriod = Carbon::now()->subDays($threshold)->format('Y-m');

        $count = Fee::where('status', 'pending')
            ->where('period', '<=', $cutoffPeriod)
            ->distinct('member_id')
            ->count('member_id');

        [$status, $color, $message] = match (true) {
            $count === 0  => ['ok',      '#34d399', 'Sin alertas — todo al día'],
            $count <= 5   => ['warning', '#fbbf24', "{$count} socios con cuota vencida > {$threshold} días"],
            default       => ['danger',  '#f87171', "{$count} socios con cuota vencida > {$threshold} días"],
        };

        return [
            'count'     => $count,
            'status'    => $status,
            'color'     => $color,
            'message'   => $message,
            'threshold' => $threshold,
        ];
    }
}
