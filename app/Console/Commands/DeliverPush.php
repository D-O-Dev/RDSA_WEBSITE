<?php

namespace App\Console\Commands;

use App\Models\Edition;
use App\Models\SiteUpdate;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeliverPush extends Command
{
    protected $signature = 'rdsa:deliver-push';

    protected $description = 'Préparer les alertes d’annonces et transmettre les notifications autorisées';

    public function handle(PushNotificationService $service): int
    {
        $edition = Edition::where('is_active', true)->where('is_published', true)->where('is_archived', false)->first();
        foreach (SiteUpdate::visibleFor($edition)->where('kind', 'announcement')->whereNull('push_notified_at')->orderBy('id')->limit(20)->get() as $announcement) {
            DB::transaction(function () use ($service, $announcement) {
                $record = SiteUpdate::whereKey($announcement->id)->lockForUpdate()->first();
                if (! $record || $record->push_notified_at) {
                    return;
                }
                $service->enqueue('announcement-'.$record->id.'-'.$record->updated_at->timestamp, false, 'Une nouvelle annonce est disponible sur le site.', '/#contact');
                DB::table('site_updates')->where('id', $record->id)->update(['push_notified_at' => now()]);
            });
        }
        $this->info($service->deliver().' notifications examinées.');

        return self::SUCCESS;
    }
}
