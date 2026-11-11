<?php

namespace App\Console\Commands;

use App\Models\Fee;
use App\Models\Member;
use Illuminate\Console\Command;

class GenerateMonthlyFees extends Command
{
    protected $signature   = 'fees:generate {period? : Período en formato YYYY-MM (default: mes actual)}';
    protected $description = 'Genera cuotas mensuales para todos los socios activos';

    public function handle(): int
    {
        $period = $this->argument('period') ?? now()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $this->error("Formato de período inválido: {$period}. Use YYYY-MM.");
            return self::FAILURE;
        }

        $members = Member::where('status', 'active')->with('feeCategory')->get();
        $created = 0;
        $skipped = 0;

        foreach ($members as $member) {
            $exists = Fee::where('member_id', $member->id)
                ->where('period', $period)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Fee::create([
                'member_id' => $member->id,
                'period'    => $period,
                'amount'    => $member->feeCategory->amount,
                'status'    => 'pending',
            ]);
            $created++;
        }

        $this->info("Período {$period}: {$created} cuotas generadas, {$skipped} omitidas.");
        return self::SUCCESS;
    }
}
