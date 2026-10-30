<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
        \Illuminate\Support\Facades\Gate::define('access-admin', fn ($user) => $user->is_active && ($user->is_admin || $user->team_role === 'controller'));
        \Illuminate\Support\Facades\Gate::define('manage-team', fn ($user) => $user->is_active && $user->is_admin);
        \Illuminate\Support\Facades\Gate::define('use-admin-route', fn ($user) => $user->is_admin || request()->routeIs('admin.check-in.*', 'admin.attendances.index', 'admin.account.*'));
        //
    }
}
