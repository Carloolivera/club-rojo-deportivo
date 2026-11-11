<?php

namespace App\Filament\Pages;

use App\Models\Fee;
use App\Models\Member;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class ReportsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.reports-page';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes';

    protected static ?int $navigationSort = 10;

    public string $selectedPeriod = '';

    public function mount(): void
    {
        $this->selectedPeriod = now()->format('Y-m');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('view-reports');
    }

    public function getActiveMembers(): int
    {
        return Member::where('status', 'active')->count();
    }

    public function getInactiveMembers(): int
    {
        return Member::where('status', 'inactive')->count();
    }

    public function getLeavesCount(): int
    {
        [$year, $month] = explode('-', $this->selectedPeriod);

        return Member::where('status', 'inactive')
            ->whereYear('updated_at', $year)
            ->whereMonth('updated_at', $month)
            ->count();
    }

    public function getIncome(): float
    {
        return (float) Fee::where('status', 'paid')
            ->where('period', $this->selectedPeriod)
            ->sum('amount');
    }

    public function exportExcel(): void
    {
        $this->redirect(route('reports.export.excel'));
    }

    public function exportMembersPdf(): void
    {
        $this->redirect(route('reports.export.pdf'));
    }

    public function exportFeesPdf(): void
    {
        $this->redirect(route('fees.export.pdf', ['period' => $this->selectedPeriod]));
    }

    public function exportOverduePdf(): void
    {
        $this->redirect(route('reports.export.overdue'));
    }

    public function exportOverdueExcel(): void
    {
        $this->redirect(route('reports.export.overdue.excel'));
    }

    public function exportFeesExcel(): void
    {
        $this->redirect(route('fees.export.excel', ['period' => $this->selectedPeriod]));
    }

    /** @deprecated use exportMembersPdf */
    public function exportPdf(): void
    {
        $this->exportMembersPdf();
    }

    protected function getViewData(): array
    {
        $period = $this->selectedPeriod;

        return Cache::remember("report_page_{$period}", now()->addMinutes(10), function () {
            return [
                'activeMembers'   => $this->getActiveMembers(),
                'inactiveMembers' => $this->getInactiveMembers(),
                'leavesCount'     => $this->getLeavesCount(),
                'income'          => $this->getIncome(),
            ];
        });
    }
}
