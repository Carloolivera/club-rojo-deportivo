<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MembersPerSportWidget extends Widget
{
    protected string $view = 'filament.widgets.members-per-sport-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Gate::allows('view-reports');
    }

    public function getData(): array
    {
        $total = Member::active()->count();

        $bySport = DB::table('members')
            ->join('sports', 'members.sport_id', '=', 'sports.id')
            ->where('members.status', 'active')
            ->whereNull('members.deleted_at')
            ->select('sports.name as sport_name', DB::raw('count(*) as total'))
            ->groupBy('sports.id', 'sports.name')
            ->orderByDesc('total')
            ->get();

        $max = $bySport->max('total') ?: 1;

        return [
            'total'   => $total,
            'sports'  => $bySport->map(fn ($row) => [
                'name'  => $row->sport_name,
                'count' => $row->total,
                'pct'   => round($row->total / $max * 100),
            ])->all(),
        ];
    }
}
