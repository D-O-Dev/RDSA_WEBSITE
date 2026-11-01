<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\PushNotification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    public function keys(): array
    {
        return ApplicationSetting::firstOrCreate(['key' => 'push_vapid'], ['values' => VAPID::createVapidKeys()])->values;
    }

    public function enqueue(string $event, bool $admin, string $body, string $url): void
    {
        $query = PushSubscription::query();
        if ($admin) {
            $query->whereIn('user_id', User::where('is_active', true)->where('is_admin', true)->select('id'));
        } else {
            $query->whereNull('user_id');
        }
        $query->select('id')->chunkById(200, function ($subscriptions) use ($event, $body, $url) {
            $rows = [];
            foreach ($subscriptions as $s) {
                $rows[] = ['subscription_id' => $s->id, 'event_key' => $event,
                    'payload' => json_encode(['title' => 'RDSA', 'body' => $body, 'url' => $url, 'tag' => $event], JSON_THROW_ON_ERROR),
                    'status' => 'pending', 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()];
            }
            if ($rows) {
                DB::table('push_notifications')->insertOrIgnore($rows);
            }
        });
    }

    public function deliver(): int
    {
        $settings = ApplicationSetting::find('push_vapid')?->values;
        if (! $settings) {
            return 0;
        }
        PushNotification::where('status', 'processing')->where('attempted_at', '<', now()->subMinutes(5))->update(['status' => 'failed']);
        PushNotification::where('expires_at', '<=', now())->where('status', 'pending')->update(['status' => 'expired']);
        $push = new WebPush(['VAPID' => ['subject' => rtrim(config('app.url'), '/'), 'publicKey' => $settings['publicKey'], 'privateKey' => $settings['privateKey']]], ['TTL' => 3600], 15, ['connect_timeout' => 5, 'allow_redirects' => false]);
        $claimed = [];
        foreach (PushNotification::where('status', 'pending')->orderBy('id')->limit(100)->get() as $notification) {
            $sub = PushSubscription::find($notification->subscription_id);
            if ($sub && $sub->user_id && ! User::whereKey($sub->user_id)->where('is_active', true)->where('is_admin', true)->exists()) {
                $sub->delete();

                continue;
            }
            if ($sub && isset($claimed[$sub->subscription['endpoint']])) {
                continue;
            }
            if (! $sub || ! PushNotification::whereKey($notification->id)->where('status', 'pending')->update(['status' => 'processing', 'attempted_at' => now()])) {
                continue;
            }
            try {
                $data = $sub->subscription;
                $push->queueNotification(Subscription::create($data), json_encode($notification->payload, JSON_THROW_ON_ERROR));
                $claimed[$data['endpoint']] = $notification->id;
            } catch (\Throwable) {
                $notification->update(['status' => 'failed']);
            }
        }
        try {
            foreach ($push->flush() as $report) {
                $endpoint = (string) $report->getRequest()->getUri();
                $id = $claimed[$endpoint] ?? null;
                if (! $id) {
                    continue;
                }
                PushNotification::whereKey($id)->update(['status' => $report->isSuccess() ? 'sent' : 'failed']);
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->delete();
                }
            }
        } catch (\Throwable) { /* Les tentatives interrompues ne sont jamais renvoyées automatiquement. */
        }
        PushNotification::where('updated_at', '<', now()->subDays(7))->whereNotIn('status', ['pending', 'processing'])->delete();

        return count($claimed);
    }
}
