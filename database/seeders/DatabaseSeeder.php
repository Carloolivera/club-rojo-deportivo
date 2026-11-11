<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RoleSeeder::class]);

        if (App::isProduction()) {
            return;
        }

        $this->call([
            SportSeeder::class,
            FeeCategorySeeder::class,
            LeaveReasonSeeder::class,
            MemberSeeder::class,
        ]);
    }
}
