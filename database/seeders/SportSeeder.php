<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    public function run(): void
    {
        $sports = ['Fútbol', 'Hockey', 'Básquet', 'Natación', 'Tenis', 'Vóley', 'Rugby', 'Atletismo'];

        foreach ($sports as $name) {
            Sport::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
