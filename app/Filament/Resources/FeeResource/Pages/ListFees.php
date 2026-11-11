<?php

namespace App\Filament\Resources\FeeResource\Pages;

use App\Filament\Resources\FeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;

class ListFees extends ListRecords
{
    protected static string $resource = FeeResource::class;

    protected function getHeaderActions(): array
    {
        $period = now()->format('Y-m');

        return [
            Actions\CreateAction::make(),
            Actions\Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => Gate::allows('view-reports'))
                ->url(route('fees.export.excel', ['period' => $period]))
                ->openUrlInNewTab(),
            Actions\Action::make('exportPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->visible(fn () => Gate::allows('view-reports'))
                ->url(route('fees.export.pdf', ['period' => $period]))
                ->openUrlInNewTab(),
        ];
    }
}
