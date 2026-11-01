<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\Edition;
use App\Models\Participation;
use App\Services\PushNotificationService;
use App\Support\SiteDesign;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
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
        ResetPassword::createUrlUsing(fn ($user, $token) => route('password.reset', ['token' => $token, 'email' => $user->email]));
        foreach ([Participation::class => ['reservation', 'Une nouvelle réservation attend une vérification.', '/admin/participations'],
            ContactMessage::class => ['message', 'Un nouveau message attend une réponse.', '/admin/messages']] as $model => [$event, $body, $url]) {
            $model::created(function ($record) use ($event, $body, $url) {
                DB::afterCommit(function () use ($record, $event, $body, $url) {
                    try {
                        app(PushNotificationService::class)->enqueue($event.'-'.$record->id, true, $body, $url);
                    } catch (\Throwable) {
                        Log::warning('Préparation d’une notification indisponible.');
                    }
                });
            });
        }
        View::composer('admin.layout', function ($view) {
            $view->with('unreadMessageCount', auth()->user()?->is_admin ? ContactMessage::where('status', 'new')->count() : 0);
        });
        View::composer('layouts.app', function ($view) {
            $data = $view->getData();
            $edition = $data['edition'] ?? ($data['participation']->edition ?? null);
            if (! $edition) {
                $edition = Edition::where('is_active', true)->where('is_published', true)->where('is_archived', false)->orderByDesc('starts_at')->first();
            }
            $view->with('siteDesign', SiteDesign::resolve($edition));
        });
    }
}
