<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Imports\MembersImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\CreateAction::make(),
        ];

        if (Gate::allows('view-reports')) {
            $actions[] = Actions\Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('reports.export.excel'))
                ->openUrlInNewTab();
        }

        if (Gate::allows('manage-members')) {
            $actions[] = Actions\Action::make('importCsv')
                ->label('Importar CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo CSV / Excel')
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/plain',
                        ])
                        ->disk('local')
                        ->directory('imports')
                        ->helperText('Columnas requeridas: nombre, dni, deporte, categoria_cuota. Opcionales: email, telefono, fecha_nacimiento, estado'),
                ])
                ->action(function (array $data): void {
                    Storage::disk('local')->makeDirectory('imports');
                    $path = Storage::disk('local')->path($data['file']);
                    $import = new MembersImport();
                    Excel::import($import, $path);

                    $body = "Importados: {$import->imported} | Omitidos: {$import->skipped}";

                    if ($import->errors) {
                        $errList = implode("\n", array_slice($import->errors, 0, 10));
                        if (count($import->errors) > 10) {
                            $errList .= "\n...y " . (count($import->errors) - 10) . " errores más.";
                        }
                        Notification::make()
                            ->title('Importación con errores')
                            ->body($body . "\n\n" . $errList)
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Importación exitosa')
                            ->body($body)
                            ->success()
                            ->send();
                    }
                });
        }

        return $actions;
    }
}
