<?php

namespace App\Console\Commands;

use App\Models\Fee;
use Illuminate\Console\Command;

class MarkOverdueFees extends Command
{
    protected $signature   = 'fees:mark-overdue';
    protected $description = 'Marca como overdue las cuotas pending de períodos anteriores al mes actual';

    public function handle(): int
    {
        $currentPeriod = now()->format('Y-m');

        $count = Fee::where('status', 'pending')
            ->where('period', '<', $currentPeriod)
            ->update(['status' => 'overdue']);

        $this->info("fees:mark-overdue: {$count} cuota(s) marcadas como overdue.");

        return self::SUCCESS;
    }
}
