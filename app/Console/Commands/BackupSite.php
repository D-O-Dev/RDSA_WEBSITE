<?php

namespace App\Console\Commands;

use App\Models\AdminAction;
use App\Models\ApplicationSetting;
use App\Services\SiteBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackupSite extends Command
{
    protected $signature = 'rdsa:backup-site';

    protected $description = 'Exécuter la sauvegarde quotidienne configurée dans l’administration';

    public function handle(SiteBackupService $service): int
    {
        $settings = ApplicationSetting::find('backups')?->values ?? [];
        if (! ($settings['enabled'] ?? false) || ($settings['time'] ?? '02:00') !== now()->format('H:i') || ($settings['last_date'] ?? null) === today()->toDateString()) {
            return self::SUCCESS;
        }
        $id = $service->create();
        $ids = [...($settings['automatic_ids'] ?? []), $id];
        foreach (array_slice($ids, 0, -3) as $old) {
            if (Str::isUuid($old)) {
                @unlink($service->directory().'/'.$old.'.zip');
            }
        }
        $settings['automatic_ids'] = array_slice($ids, -3);
        $settings['last_date'] = today()->toDateString();
        ApplicationSetting::updateOrCreate(['key' => 'backups'], ['values' => $settings]);
        AdminAction::create(['actor_id' => null, 'actor_name' => 'Planificateur', 'action' => 'maintenance.backup', 'target' => 'Sauvegarde '.$id, 'created_at' => now()]);
        $this->info('Sauvegarde quotidienne créée.');

        return self::SUCCESS;
    }
}
