<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getColumns(): array|int
    {
        return [
            'sm'  => 1,
            'lg'  => 2,
        ];
    }
}
