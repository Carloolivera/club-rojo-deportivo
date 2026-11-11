<?php

namespace Database\Seeders;

use App\Models\FeeCategory;
use Illuminate\Database\Seeder;

class FeeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Infantil', 'amount' => 3000],
            ['name' => 'Junior',   'amount' => 5000],
            ['name' => 'Adulto',   'amount' => 8000],
            ['name' => 'Senior',   'amount' => 6000],
            ['name' => 'Familiar', 'amount' => 12000],
        ];

        foreach ($categories as $category) {
            FeeCategory::firstOrCreate(
                ['name' => $category['name']],
                ['amount' => $category['amount'], 'is_active' => true]
            );
        }
    }
}
