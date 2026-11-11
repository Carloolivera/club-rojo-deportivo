<?php

namespace Database\Seeders;

use App\Models\LeaveReason;
use Illuminate\Database\Seeder;

class LeaveReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = ['Viaje', 'Económica', 'Lesión', 'Estudios', 'Trabajo', 'Personal', 'Otros'];

        foreach ($reasons as $name) {
            LeaveReason::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
