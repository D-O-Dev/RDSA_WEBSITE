<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\View::composer('admin.layout', function ($view) {
            $view->with('unreadMessageCount', auth()->user()?->is_admin ? \App\Models\ContactMessage::where('status', 'new')->count() : 0);
        });
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $data = $view->getData();
            $edition = $data['edition'] ?? ($data['participation']->edition ?? null);
            if (!$edition) $edition = \App\Models\Edition::where('is_active', true)->where('is_published', true)->where('is_archived', false)->orderByDesc('starts_at')->first();
            $view->with('siteDesign', \App\Support\SiteDesign::resolve($edition));
        });
    }
}
