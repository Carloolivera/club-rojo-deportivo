<?php

namespace App\Console\Commands;

use App\Mail\OverdueFeeNotification;
use App\Models\Fee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendOverdueFeeAlerts extends Command
{
    protected $signature = 'fees:send-alerts {--force : Ignora cache de idempotencia}';
    protected $description = 'Envía alertas de cuotas vencidas a admin y managers';

    public function handle(): int
    {
        $cacheKey = 'alerts:overdue:sent:' . now()->toDateString();

        if (! $this->option('force') && ! Cache::add($cacheKey, true, now()->endOfDay())) {
            $this->warn('Alertas ya enviadas hoy. Use --force para reenviar.');
            return self::SUCCESS;
        }

        $threshold = config('alerts.days_threshold');
        $cutoffPeriod = Carbon::now()->subDays($threshold)->format('Y-m');

        $fees = Fee::with('member')
            ->where('status', 'overdue')
            ->where('period', '<=', $cutoffPeriod)
            ->get();

        if ($fees->isEmpty()) {
            $this->info('No hay socios morosos que superen el threshold.');
            return self::SUCCESS;
        }

        $recipients = User::role(['admin', 'manager'])
            ->whereNotNull('email')
            ->get();

        foreach ($recipients as $user) {
            Mail::to($user)->send(new OverdueFeeNotification($fees, $threshold));
        }

        $this->info("Alertas enviadas: {$fees->count()} socios morosos notificados a {$recipients->count()} destinatarios.");
        return self::SUCCESS;
    }
}
