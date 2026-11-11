<?php

namespace Database\Seeders;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\MemberPeriod;
use App\Models\Sport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2ESeeder extends Seeder
{
    public function run(): void
    {
        // Creates a user with a well-known password: never on a public server.
        abort_if(app()->isProduction(), 403, 'E2ESeeder is for local test runs only.');

        $this->call([
            RoleSeeder::class,
            SportSeeder::class,
            FeeCategorySeeder::class,
            LeaveReasonSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@club.com'],
            [
                'name' => 'Admin E2E',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        $sport = Sport::where('name', 'Fútbol')->first();
        $category = FeeCategory::where('name', 'Adulto')->first();

        $member = Member::firstOrCreate(
            ['dni' => '30123456'],
            [
                'name' => 'Juan Pérez',
                'email' => 'juan@example.com',
                'phone' => '11-000001',
                'birthdate' => Carbon::parse('1990-05-15'),
                'is_minor' => false,
                'sport_id' => $sport->id,
                'fee_category_id' => $category->id,
                'status' => 'active',
            ]
        );

        MemberPeriod::firstOrCreate(
            ['member_id' => $member->id],
            [
                'sport_id' => $sport->id,
                'started_at' => Carbon::now()->subMonths(6),
                'ended_at' => null,
            ]
        );

        $period = Carbon::now()->format('Y-m');
        Fee::firstOrCreate(
            ['member_id' => $member->id, 'period' => $period],
            [
                'amount' => $category->amount,
                'status' => 'pending',
                'paid_at' => null,
                'payment_method' => null,
            ]
        );

        $overdueMonth = Carbon::now()->subMonth()->format('Y-m');
        Fee::firstOrCreate(
            ['member_id' => $member->id, 'period' => $overdueMonth],
            [
                'amount' => $category->amount,
                'status' => 'overdue',
                'paid_at' => null,
                'payment_method' => null,
            ]
        );
    }
}
