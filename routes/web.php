<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AppearanceController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BulkController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EditionController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\MailSettingController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\ReservationManagementController;
use App\Http\Controllers\Admin\SiteContentController;
use App\Http\Controllers\Admin\SiteUpdateController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Admin\WhatsappController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\MediaGalleryController;
use App\Http\Controllers\ParticipationController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Middleware\DefaultEdition;
use App\Http\Middleware\RecordAdminAction;
use App\Models\Edition;
use App\Models\SiteContent;
use App\Models\SiteUpdate;
use App\Services\ContactMessageService;
use App\Support\PublicMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/health', function () {
    try {
        DB::select('select 1');

        return response()->json(['status' => 'ok'])->header('Cache-Control', 'no-store');
    } catch (Throwable) {
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
    ['mediaItems' => $mediaItems, 'mediaTotals' => $mediaTotals, 'mediaAlbums' => $mediaAlbums] = PublicMedia::overview();
    $contactSubmissionToken = app(ContactMessageService::class)->formToken(request());
    $siteContent = SiteContent::current();
    $updates = SiteUpdate::visibleFor($edition)->orderBy('sort_order')->orderByDesc('id')->get();
    $announcements = $updates->where('kind', 'announcement')->values();
    $faqs = $updates->where('kind', 'faq')->values();

    return view('home', compact(
        'previousEditions',
        'submissionToken',
        'edition',
        'mediaItems', 'mediaTotals', 'mediaAlbums',
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
    $items = SiteUpdate::visibleFor($edition)->where('kind', 'announcement')->orderBy('sort_order')->orderByDesc('id')->get();

    return response()->json(['items' => $items->map(fn ($item) => $item->only(['id', 'title', 'body', 'updated_at']))])->header('Cache-Control', 'no-store');
})->middleware('throttle:120,1,public-information:')->name('public.information');

Route::get('/galerie', MediaGalleryController::class)->middleware('throttle:120,1,public-gallery:')->name('media.gallery');

Route::get('/mon-billet', [TicketController::class, 'find'])->name('tickets.find');
Route::post('/push/prepare', [PushSubscriptionController::class, 'prepare'])->middleware('throttle:10,1,push-prepare:')->name('push.prepare');
Route::post('/push/subscription', [PushSubscriptionController::class, 'store'])->middleware('throttle:10,1,push-subscribe:')->name('push.store');
Route::delete('/push/subscription', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:10,1,push-unsubscribe:')->name('push.destroy');
Route::post('/mon-billet', [TicketController::class, 'lookup'])
    ->middleware('throttle:5,1,ticket-lookup:')->name('tickets.lookup');
Route::get('/billets/{reference}/{token}', [TicketController::class, 'show'])
    ->where('reference', 'RDSA-TK-[A-Z0-9]{12}')->where('token', '[A-F0-9]{32}')
    ->middleware('throttle:60,1,ticket-read:')->name('tickets.show');

Route::get('/webhooks/whatsapp', [WhatsappWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'receive'])->middleware('throttle:300,1,whatsapp-webhook:');

Route::post('/messages', [ContactMessageController::class, 'store'])->middleware('throttle:5,1,message-create:')->name('messages.store');
Route::get('/messages/{reference}/{token}', [ContactMessageController::class, 'show'])
    ->where('reference', 'MSG-[A-Z0-9]{12}')->where('token', '[a-f0-9]{48}')->middleware('throttle:60,1,message-read:')->name('messages.show');
Route::post('/messages/{reference}/{token}', [ContactMessageController::class, 'reply'])
    ->where('reference', 'MSG-[A-Z0-9]{12}')->where('token', '[a-f0-9]{48}')->middleware('throttle:5,1,message-reply:')->name('messages.reply');

Route::get('/admin/login', [AuthController::class, 'show'])->name('login');
Route::get('/admin/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
Route::post('/admin/forgot-password', [PasswordResetController::class, 'send'])->middleware('throttle:5,1,password-recovery:')->name('password.email');
Route::get('/admin/reset-password/{token}', [PasswordResetController::class, 'form'])->name('password.reset');
Route::post('/admin/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1,password-reset:')->name('password.update');
Route::post('/admin/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1,admin-login:')->name('admin.login');
Route::post('/admin/logout', [AuthController::class, 'logout'])
    ->middleware('auth')->name('admin.logout');
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin', 'can:use-admin-route', DefaultEdition::class, RecordAdminAction::class])->group(function () {
    Route::get('/appearance', [AppearanceController::class, 'index'])->name('appearance.index');
    Route::put('/appearance/{edition}', [AppearanceController::class, 'update'])->name('appearance.update');
    Route::post('/appearance/{edition}/preview', [AppearanceController::class, 'preview'])->name('appearance.preview');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/statistics/export', [StatisticsController::class, 'export'])->name('statistics.export');
    Route::get('/exports/{kind}', ExportController::class)->whereIn('kind', ['participations', 'tickets', 'attendances'])->middleware('throttle:10,1,admin-export:')->name('exports');
    Route::post('/bulk/{kind}', BulkController::class)->whereIn('kind', ['participations', 'media'])->middleware('throttle:5,1,admin-bulk:')->name('bulk');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::get('/storage', [StorageController::class, 'index'])->middleware('can:manage-sensitive-data')->name('storage.index');
    Route::get('/backups', [BackupController::class, 'index'])->middleware('can:manage-sensitive-data')->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->middleware('can:manage-sensitive-data')->name('backups.store');
    Route::post('/backups/import', [BackupController::class, 'import'])->middleware('can:manage-sensitive-data')->name('backups.import');
    Route::delete('/backups/{id}', [BackupController::class, 'destroy'])->middleware('can:manage-sensitive-data')->name('backups.destroy');
    Route::get('/backups/{id}/download', [BackupController::class, 'download'])->middleware('can:manage-sensitive-data')->name('backups.download');
    Route::post('/backups/{id}/restore', [BackupController::class, 'restore'])->middleware(['can:manage-sensitive-data', 'throttle:3,1,backup-restore:'])->name('backups.restore');
    Route::post('/maintenance', [BackupController::class, 'maintenance'])->middleware('can:manage-sensitive-data')->name('backups.maintenance');
    Route::post('/backup-schedule', [BackupController::class, 'schedule'])->middleware('can:manage-sensitive-data')->name('backups.schedule');
    Route::delete('/storage', [StorageController::class, 'destroy'])->middleware('can:manage-sensitive-data')->name('storage.destroy');
    Route::post('/media/photos', [StorageController::class, 'uploadPhotos'])->name('media.photos');
    Route::post('/trash/{kind}/{id}/restore', [TrashController::class, 'restore'])->whereNumber('id')->name('trash.restore');
    Route::delete('/trash/{kind}/{id}', [TrashController::class, 'destroy'])->whereNumber('id')->middleware('can:manage-sensitive-data')->name('trash.destroy');
    Route::delete('/media/{mediaItem}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::delete('/participations/{participation}', [App\Http\Controllers\Admin\ParticipationController::class, 'destroy'])->middleware('throttle:5,1,reservation-delete:')->name('participations.destroy');
    Route::post('/participations/{participation}/cancel', [App\Http\Controllers\Admin\ParticipationController::class, 'cancel'])->name('participations.cancel');
    Route::get('/updates', [SiteUpdateController::class, 'index'])->name('updates.index');
    Route::post('/updates', [SiteUpdateController::class, 'store'])->name('updates.store');
    Route::put('/updates/{siteUpdate}', [SiteUpdateController::class, 'update'])->name('updates.update');
    Route::patch('/updates/{siteUpdate}/publication', [SiteUpdateController::class, 'publish'])->name('updates.publish');
    Route::delete('/updates/{siteUpdate}', [SiteUpdateController::class, 'destroy'])->name('updates.destroy');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/events', [CalendarController::class, 'store'])->name('calendar.store');
    Route::put('/calendar/events/{calendarEvent}', [CalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/events/{calendarEvent}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team', [TeamController::class, 'store'])->middleware('throttle:10,1,team-write:')->name('team.store');
    Route::put('/team/{user}', [TeamController::class, 'update'])->middleware('throttle:10,1,team-write:')->name('team.update');
    Route::delete('/team/{user}', [TeamController::class, 'destroy'])->middleware('can:manage-sensitive-data')->name('team.destroy');
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::post('/account/push/prepare', [PushSubscriptionController::class, 'prepare'])->name('account.push.prepare');
    Route::post('/account/push/subscription', [PushSubscriptionController::class, 'store'])->name('account.push.store');
    Route::delete('/account/push/subscription', [PushSubscriptionController::class, 'destroy'])->name('account.push.destroy');
    Route::post('/account/two-factor/setup', [TwoFactorController::class, 'setup'])->middleware('throttle:5,1,two-factor-setup:')->name('two-factor.setup');
    Route::get('/account/two-factor/setup', [TwoFactorController::class, 'pending'])->name('two-factor.pending');
    Route::post('/account/two-factor/enable', [TwoFactorController::class, 'enable'])->middleware('throttle:5,1,two-factor-enable:')->name('two-factor.enable');
    Route::delete('/account/two-factor', [TwoFactorController::class, 'disable'])->middleware('throttle:5,1,two-factor-disable:')->name('two-factor.disable');
    Route::put('/account/profile', [AccountController::class, 'profile'])->middleware('throttle:5,1,account-profile:')->name('account.profile');
    Route::post('/account/sessions/revoke', [AccountController::class, 'revokeSessions'])->middleware('throttle:5,1,account-sessions:')->name('account.sessions.revoke');
    Route::put('/account', [AccountController::class, 'update'])->middleware('throttle:5,1,account-password:')->name('account.update');
    Route::get('/content', [SiteContentController::class, 'edit'])->name('content.edit');
    Route::put('/content', [SiteContentController::class, 'update'])->name('content.update');
    Route::get('/', [EditionController::class, 'index'])->name('index');
    Route::post('/editions', [EditionController::class, 'store'])->name('editions.store');
    Route::put('/editions/{edition}', [EditionController::class, 'update'])->name('editions.update');
    Route::post('/editions/{edition}/duplicate', [EditionController::class, 'duplicate'])->name('editions.duplicate');
    Route::delete('/editions/{edition}', [EditionController::class, 'destroy'])->middleware('can:manage-sensitive-data')->name('editions.destroy');
    Route::get('/participations', [App\Http\Controllers\Admin\ParticipationController::class, 'index'])->name('participations.index');
    Route::get('/participations/create', [ReservationManagementController::class, 'create'])->name('participations.create');
    Route::post('/participations', [ReservationManagementController::class, 'store'])->middleware('throttle:30,1,assisted-reservation:')->name('participations.store');
    Route::get('/participations/{participation}/manage', [ReservationManagementController::class, 'edit'])->name('participations.manage');
    Route::put('/participations/{participation}/manage', [ReservationManagementController::class, 'update'])->name('participations.correct');
    Route::post('/participations/{participation}/undo-entry', [ReservationManagementController::class, 'undoEntry'])->middleware('can:manage-sensitive-data')->name('participations.undo-entry');
    Route::post('/participations/{participation}/purge-test', [ReservationManagementController::class, 'purgeTest'])->middleware(['can:manage-sensitive-data', 'throttle:5,1,test-purge:'])->name('participations.purge-test');
    Route::post('/participations/{participation}/reopen', [ReservationManagementController::class, 'reopen'])->middleware('can:manage-sensitive-data')->name('participations.reopen');
    Route::post('/participations/{participation}/replace-ticket', [ReservationManagementController::class, 'replaceTicket'])->middleware('can:manage-sensitive-data')->name('participations.replace-ticket');
    Route::post('/participations/{participation}/review', [App\Http\Controllers\Admin\ParticipationController::class, 'review'])->name('participations.review');
    Route::get('/tickets', [App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/participations/{participation}/ticket', [App\Http\Controllers\Admin\TicketController::class, 'issue'])->name('tickets.issue');
    Route::get('/whatsapp', [WhatsappController::class, 'edit'])->name('whatsapp.edit');
    Route::get('/mail-settings', [MailSettingController::class, 'edit'])->middleware('can:manage-sensitive-data')->name('mail.edit');
    Route::put('/mail-settings', [MailSettingController::class, 'update'])->middleware('can:manage-sensitive-data')->name('mail.update');
    Route::put('/whatsapp', [WhatsappController::class, 'update'])->name('whatsapp.update');
    Route::post('/whatsapp/{delivery}/resolve', [WhatsappController::class, 'resolve'])->middleware('can:manage-sensitive-data')->name('whatsapp.resolve');
    Route::post('/tickets/{ticket}/whatsapp', [WhatsappController::class, 'retry'])->middleware('throttle:10,1,whatsapp-retry:')->name('whatsapp.retry');
    Route::get('/check-in', [CheckInController::class, 'index'])->name('check-in.index');
    Route::post('/check-in/inspect', [CheckInController::class, 'inspect'])->middleware('throttle:120,1,check-in-inspect:')->name('check-in.inspect');
    Route::post('/check-in/confirm', [CheckInController::class, 'confirm'])->middleware('throttle:120,1,check-in-confirm:')->name('check-in.confirm');
    Route::get('/attendances', [CheckInController::class, 'attendances'])->name('attendances.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::put('/media/{mediaItem}', [MediaController::class, 'update'])->name('media.update');
    Route::patch('/media/{mediaItem}/publication', [MediaController::class, 'publish'])->name('media.publish');
    Route::get('/messages', [App\Http\Controllers\Admin\ContactMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/count', [App\Http\Controllers\Admin\ContactMessageController::class, 'count'])->name('messages.count');
    Route::get('/messages/{message}', [App\Http\Controllers\Admin\ContactMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/reply', [App\Http\Controllers\Admin\ContactMessageController::class, 'reply'])->name('messages.reply');
    Route::post('/messages/{message}/archive', [App\Http\Controllers\Admin\ContactMessageController::class, 'archive'])->name('messages.archive');
    Route::delete('/messages/{message}', [App\Http\Controllers\Admin\ContactMessageController::class, 'destroy'])->middleware('can:manage-sensitive-data')->name('messages.destroy');
});
