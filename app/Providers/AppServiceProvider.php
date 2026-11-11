<?php

namespace App\Providers;

use App\Models\Fee;
use App\Models\Member;
use App\Models\Reenrollment;
use App\Policies\FeePolicy;
use App\Policies\MemberPolicy;
use App\Policies\ReeenrollmentPolicy;
use App\Policies\ReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(Fee::class, FeePolicy::class);
        Gate::policy(Reenrollment::class, ReeenrollmentPolicy::class);
        Gate::define('view-reports', [ReportPolicy::class, 'view']);
        Gate::define('erase-member-data', fn ($user) => $user->hasRole('admin'));
        Gate::define('export-member-data', fn ($user) => $user->hasRole('admin'));
        Gate::define('send-broadcast', fn ($user) => $user->hasRole('admin'));
        Gate::define('manage-members', fn ($user) => $user->hasRole('admin'));
    }
}
