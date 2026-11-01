<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn ($user) => $user->is_active && ($user->is_admin || $user->team_role === 'controller'));
        Gate::define('manage-team', fn ($user) => $user->is_active && $user->is_admin);
        Gate::define('manage-sensitive-data', fn ($user) => $user->is_active && $user->is_admin && $user->is_super_admin);
        Gate::define('use-admin-route', fn ($user) => $user->is_admin || request()->routeIs('admin.check-in.*', 'admin.attendances.index', 'admin.account.*', 'admin.two-factor.*'));
        //
    }
}
