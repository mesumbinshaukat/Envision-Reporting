<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\EmployeeIpWhitelist;
use App\Policies\EmployeeIpWhitelistPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Gate to resolve user from both guards
        Gate::before(function ($user, $ability) {
            // If no user from default guard, try employee guard
            if (!$user && auth()->guard('employee')->check()) {
                return null; // Let the policy handle it
            }
        });

        Gate::policy(EmployeeIpWhitelist::class, EmployeeIpWhitelistPolicy::class);
    }
}
