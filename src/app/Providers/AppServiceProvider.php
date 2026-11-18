<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Admins can do everything, so no other check has to mention them.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        // Only admins pass this one, through the rule above.
        Gate::define('manage-users', fn () => false);
    }
}
