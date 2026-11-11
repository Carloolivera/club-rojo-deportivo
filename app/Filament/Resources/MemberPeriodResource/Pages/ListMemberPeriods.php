<?php

namespace App\Filament\Resources\MemberPeriodResource\Pages;

use App\Filament\Resources\MemberPeriodResource;
use Filament\Resources\Pages\ListRecords;

class ListMemberPeriods extends ListRecords
{
    protected static string $resource = MemberPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
