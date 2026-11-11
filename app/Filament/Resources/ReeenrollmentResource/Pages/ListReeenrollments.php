<?php

namespace App\Filament\Resources\ReeenrollmentResource\Pages;

use App\Filament\Resources\ReeenrollmentResource;
use Filament\Resources\Pages\ListRecords;

class ListReeenrollments extends ListRecords
{
    protected static string $resource = ReeenrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
