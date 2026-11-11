<?php

namespace App\Filament\Resources\LeaveReasonResource\Pages;

use App\Filament\Resources\LeaveReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveReasons extends ListRecords
{
    protected static string $resource = LeaveReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
