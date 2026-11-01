<?php

use App\Http\Controllers\ParticipationController;
use App\Models\Edition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::select('select 1');
        return response()->json(['status' => 'ok'])->header('Cache-Control', 'no-store');
    } catch (\Throwable) {
        return response()->json(['status' => 'unavailable'], 503)->header('Cache-Control', 'no-store');
    }
})->name('health');

Route::get('/', function () {
    $edition = Edition::query()
        ->where('is_active', true)
        ->where('is_published', true)
        ->where('is_archived', false)
        ->orderByDesc('starts_at')
        ->first();

    $submissionToken = (string) Str::uuid();

    $tokens = session('reservation_form_tokens', []);
    $tokens[] = $submissionToken;
    $times = array_intersect_key(session('reservation_form_times', []), array_flip(array_slice($tokens, -10)));
    $times[$submissionToken] = now()->timestamp;
    session(['reservation_form_times' => $times]);
    session(['reservation_form_tokens' => array_slice($tokens, -10)]);
    $previousEditions = Edition::where('is_active', false)->where('is_published', true)->orderByDesc('starts_at')->get();
    $mediaItems = \App\Models\MediaItem::with('edition')->visible()->orderBy('sort_order')->orderByDesc('id')->get()->map(fn($item) => $item->publicData())->values();
    $contactSubmissionToken = app(\App\Services\ContactMessageService::class)->formToken(request());
    $siteContent = \App\Models\SiteContent::current();
    $updates = \App\Models\SiteUpdate::visibleFor($edition)->orderBy('sort_order')->orderByDesc('id')->get();
    $announcements = $updates->where('kind', 'announcement')->values();
    $faqs = $updates->where('kind', 'faq')->values();

    return view('home', compact(
        'previousEditions',
        'submissionToken',
        'edition',
        'mediaItems',
        'contactSubmissionToken',
        'siteContent',
        'announcements',
        'faqs'
    ));
})->name('home');

Route::post('/participations', [
    ParticipationController::class,
    'store',
])->middleware('throttle:reservations')->name('participations.store');

Route::get('/participations/confirmation/{reference}', [
    ParticipationController::class,
    'confirmation',
])->name('participations.confirmation');

Route::get('/informations', function () {
    $edition = Edition::where('is_active', true)->where('is_published', true)->where('is_archived', false)->orderByDesc('starts_at')->first();
    $items = \App\Models\SiteUpdate::visibleFor($edition)->where('kind', 'announcement')->orderBy('sort_order')->orderByDesc('id')->get();
    return response()->json(['items' => $items->map(fn ($item) => $item->only(['id', 'title', 'body', 'updated_at']))])->header('Cache-Control', 'no-store');
})->middleware('throttle:120,1,public-information:')->name('public.information');

Route::get('/mon-billet', [\App\Http\Controllers\TicketController::class, 'find'])->name('tickets.find');
Route::post('/mon-billet', [\App\Http\Controllers\TicketController::class, 'lookup'])
    ->middleware('throttle:5,1,ticket-lookup:')->name('tickets.lookup');
Route::get('/billets/{reference}/{token}', [\App\Http\Controllers\TicketController::class, 'show'])
    ->where('reference', 'RDSA-TK-[A-Z0-9]{12}')->where('token', '[A-F0-9]{32}')
    ->middleware('throttle:60,1,ticket-read:')->name('tickets.show');

Route::get('/webhooks/whatsapp', [\App\Http\Controllers\WhatsappWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/webhooks/whatsapp', [\App\Http\Controllers\WhatsappWebhookController::class, 'receive'])->middleware('throttle:300,1,whatsapp-webhook:');

Route::post('/messages', [\App\Http\Controllers\ContactMessageController::class, 'store'])->middleware('throttle:5,1,message-create:')->name('messages.store');
Route::get('/messages/{reference}/{token}', [\App\Http\Controllers\ContactMessageController::class, 'show'])
    ->where('reference', 'MSG-[A-Z0-9]{12}')->where('token', '[a-f0-9]{48}')->middleware('throttle:60,1,message-read:')->name('messages.show');
Route::post('/messages/{reference}/{token}', [\App\Http\Controllers\ContactMessageController::class, 'reply'])
    ->where('reference', 'MSG-[A-Z0-9]{12}')->where('token', '[a-f0-9]{48}')->middleware('throttle:5,1,message-reply:')->name('messages.reply');

Route::get('/admin/login', [\App\Http\Controllers\Admin\AuthController::class, 'show'])->name('login');
Route::post('/admin/login', [\App\Http\Controllers\Admin\AuthController::class, 'login'])
    ->middleware('throttle:5,1,admin-login:')->name('admin.login');
Route::post('/admin/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])
    ->middleware('auth')->name('admin.logout');
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin', 'can:use-admin-route', \App\Http\Middleware\RecordAdminAction::class])->group(function () {
    Route::get('/appearance', [\App\Http\Controllers\Admin\AppearanceController::class, 'index'])->name('appearance.index');
    Route::put('/appearance/{edition}', [\App\Http\Controllers\Admin\AppearanceController::class, 'update'])->name('appearance.update');
    Route::post('/appearance/{edition}/preview', [\App\Http\Controllers\Admin\AppearanceController::class, 'preview'])->name('appearance.preview');
    Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/statistics', [\App\Http\Controllers\Admin\StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/statistics/export', [\App\Http\Controllers\Admin\StatisticsController::class, 'export'])->name('statistics.export');
    Route::get('/audit', [\App\Http\Controllers\Admin\AuditController::class, 'index'])->name('audit.index');
    Route::delete('/media/{mediaItem}', [\App\Http\Controllers\Admin\MediaController::class, 'destroy'])->name('media.destroy');
    Route::delete('/participations/{participation}', [\App\Http\Controllers\Admin\ParticipationController::class, 'destroy'])->middleware('throttle:5,1,reservation-delete:')->name('participations.destroy');
    Route::post('/participations/{participation}/cancel', [\App\Http\Controllers\Admin\ParticipationController::class, 'cancel'])->name('participations.cancel');
    Route::get('/updates', [\App\Http\Controllers\Admin\SiteUpdateController::class, 'index'])->name('updates.index');
    Route::post('/updates', [\App\Http\Controllers\Admin\SiteUpdateController::class, 'store'])->name('updates.store');
    Route::put('/updates/{siteUpdate}', [\App\Http\Controllers\Admin\SiteUpdateController::class, 'update'])->name('updates.update');
    Route::patch('/updates/{siteUpdate}/publication', [\App\Http\Controllers\Admin\SiteUpdateController::class, 'publish'])->name('updates.publish');
    Route::delete('/updates/{siteUpdate}', [\App\Http\Controllers\Admin\SiteUpdateController::class, 'destroy'])->name('updates.destroy');
    Route::get('/calendar', [\App\Http\Controllers\Admin\CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/events', [\App\Http\Controllers\Admin\CalendarController::class, 'store'])->name('calendar.store');
    Route::put('/calendar/events/{calendarEvent}', [\App\Http\Controllers\Admin\CalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/events/{calendarEvent}', [\App\Http\Controllers\Admin\CalendarController::class, 'destroy'])->name('calendar.destroy');
    Route::get('/team', [\App\Http\Controllers\Admin\TeamController::class, 'index'])->name('team.index');
    Route::post('/team', [\App\Http\Controllers\Admin\TeamController::class, 'store'])->middleware('throttle:10,1,team-write:')->name('team.store');
    Route::put('/team/{user}', [\App\Http\Controllers\Admin\TeamController::class, 'update'])->middleware('throttle:10,1,team-write:')->name('team.update');
    Route::get('/account', [\App\Http\Controllers\Admin\AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account/profile', [\App\Http\Controllers\Admin\AccountController::class, 'profile'])->middleware('throttle:5,1,account-profile:')->name('account.profile');
    Route::post('/account/sessions/revoke', [\App\Http\Controllers\Admin\AccountController::class, 'revokeSessions'])->middleware('throttle:5,1,account-sessions:')->name('account.sessions.revoke');
    Route::put('/account', [\App\Http\Controllers\Admin\AccountController::class, 'update'])->middleware('throttle:5,1,account-password:')->name('account.update');
    Route::get('/content', [\App\Http\Controllers\Admin\SiteContentController::class, 'edit'])->name('content.edit');
    Route::put('/content', [\App\Http\Controllers\Admin\SiteContentController::class, 'update'])->name('content.update');
    Route::get('/', [\App\Http\Controllers\Admin\EditionController::class, 'index'])->name('index');
    Route::post('/editions', [\App\Http\Controllers\Admin\EditionController::class, 'store'])->name('editions.store');
    Route::put('/editions/{edition}', [\App\Http\Controllers\Admin\EditionController::class, 'update'])->name('editions.update');
    Route::get('/participations', [\App\Http\Controllers\Admin\ParticipationController::class, 'index'])->name('participations.index');
    Route::post('/participations/{participation}/review', [\App\Http\Controllers\Admin\ParticipationController::class, 'review'])->name('participations.review');
    Route::get('/tickets', [\App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/participations/{participation}/ticket', [\App\Http\Controllers\Admin\TicketController::class, 'issue'])->name('tickets.issue');
    Route::get('/whatsapp', [\App\Http\Controllers\Admin\WhatsappController::class, 'edit'])->name('whatsapp.edit');
    Route::put('/whatsapp', [\App\Http\Controllers\Admin\WhatsappController::class, 'update'])->name('whatsapp.update');
    Route::post('/tickets/{ticket}/whatsapp', [\App\Http\Controllers\Admin\WhatsappController::class, 'retry'])->middleware('throttle:10,1,whatsapp-retry:')->name('whatsapp.retry');
    Route::get('/check-in', [\App\Http\Controllers\Admin\CheckInController::class, 'index'])->name('check-in.index');
    Route::post('/check-in/inspect', [\App\Http\Controllers\Admin\CheckInController::class, 'inspect'])->middleware('throttle:120,1,check-in-inspect:')->name('check-in.inspect');
    Route::post('/check-in/confirm', [\App\Http\Controllers\Admin\CheckInController::class, 'confirm'])->middleware('throttle:120,1,check-in-confirm:')->name('check-in.confirm');
    Route::get('/attendances', [\App\Http\Controllers\Admin\CheckInController::class, 'attendances'])->name('attendances.index');
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/media', [\App\Http\Controllers\Admin\MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [\App\Http\Controllers\Admin\MediaController::class, 'store'])->name('media.store');
    Route::put('/media/{mediaItem}', [\App\Http\Controllers\Admin\MediaController::class, 'update'])->name('media.update');
    Route::patch('/media/{mediaItem}/publication', [\App\Http\Controllers\Admin\MediaController::class, 'publish'])->name('media.publish');
    Route::get('/messages', [\App\Http\Controllers\Admin\ContactMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/count', [\App\Http\Controllers\Admin\ContactMessageController::class, 'count'])->name('messages.count');
    Route::get('/messages/{message}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/reply', [\App\Http\Controllers\Admin\ContactMessageController::class, 'reply'])->name('messages.reply');
    Route::post('/messages/{message}/archive', [\App\Http\Controllers\Admin\ContactMessageController::class, 'archive'])->name('messages.archive');
});
