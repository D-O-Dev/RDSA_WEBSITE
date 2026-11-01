<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class SiteBackupService
{
    // Les comptes, sessions, clés d'hébergement et journaux ne sont jamais remplacés.
    private const TABLES = ['wave_numbers', 'editions', 'participants', 'participations', 'payments', 'payment_proofs', 'tickets',
        'whatsapp_deliveries', 'attendances', 'media_items', 'site_updates', 'calendar_events', 'contact_messages', 'contact_replies', 'site_contents', 'whatsapp_settings'];

    public function directory(): string
    {
        $path = storage_path('app/rdsa/backups/admin');
        File::ensureDirectoryExists($path, 0700);

        return $path;
    }

    public function path(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);
        $path = $this->directory().'/'.$id.'.zip';
        abort_unless(is_file($path) && ! is_link($path), 404);

        return $path;
    }

    public function listing(): array
    {
        return collect(File::files($this->directory()))->filter(fn ($f) => $f->getExtension() === 'zip' && Str::isUuid($f->getFilenameWithoutExtension()))
            ->sortByDesc(fn ($f) => $f->getMTime())->map(fn ($f) => ['id' => $f->getFilenameWithoutExtension(), 'time' => $f->getMTime(), 'size' => $f->getSize()])->values()->all();
    }

    private function schema(): array
    {
        $schema = [];
        foreach (self::TABLES as $table) {
            $schema[$table] = Schema::getColumnListing($table);
        }

return $schema;
    }

    public function create(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['backup' => 'L’extension ZIP de PHP doit être activée sur l’hébergement.']);
        }
        $id = (string) Str::uuid();
        $path = $this->directory().'/'.$id.'.zip';
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new \RuntimeException('Impossible de créer la sauvegarde.');
        }
        try {
            $data = ['version' => 1, 'created_at' => now()->toIso8601String(), 'schema' => $this->schema(), 'tables' => [], 'files' => []];
            DB::transaction(function () use (&$data) {
                foreach (self::TABLES as $table) {
                    $data['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
                }
            });
            $disk = Storage::disk('public');
            $bytes = 0;
            foreach (['rdsa/media', 'rdsa/visuals'] as $directory) {
                foreach ($disk->files($directory) as $file) {
                    if (is_link($disk->path($file)) || ! preg_match('#^rdsa/(media|visuals)/[A-Za-z0-9_.-]+\.(jpg|jpeg|png|webp)$#i', $file)) {
                        continue;
                    }
                    $size = $disk->size($file);
                    $bytes += $size;
                    if ($bytes > 100 * 1024 * 1024) {
                        throw ValidationException::withMessages(['backup' => 'Les images téléversées dépassent 100 Mo. Prévois une sauvegarde via l’hébergement pour ce volume.']);
                    }
                    $data['files'][$file] = ['size' => $size, 'sha256' => hash_file('sha256', $disk->path($file))];
                    if (! $zip->addFile($disk->path($file), 'uploads/'.$file)) {
                        throw new \RuntimeException('Image non sauvegardée.');
                    }
                }
            }
            $payload = gzencode(json_encode($data, JSON_THROW_ON_ERROR), 6);
            if (! $zip->addFromString('snapshot.enc', Crypt::encryptString($payload)) || ! $zip->close()) {
                throw new \RuntimeException('Sauvegarde incomplète.');
            }
            chmod($path, 0600);

            return $id;
        } catch (\Throwable $e) {
            $zip->close();
            @unlink($path);
            throw $e;
        }
    }

    public function restore(string $id): void
    {
        $zip = new ZipArchive;
        if ($zip->open($this->path($id)) !== true) {
            throw ValidationException::withMessages(['backup' => 'Sauvegarde illisible.']);
        }
        try {
            $encrypted = $zip->getFromName('snapshot.enc');
            if (! is_string($encrypted) || strlen($encrypted) > 100 * 1024 * 1024) {
                throw new \RuntimeException('Format invalide.');
            }
            $plain = gzdecode(Crypt::decryptString($encrypted), 100 * 1024 * 1024);
            if ($plain === false) {
                throw new \RuntimeException('Sauvegarde corrompue.');
            }
            $data = json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
            if (($data['version'] ?? null) !== 1 || ($data['schema'] ?? null) !== $this->schema() || array_keys($data['tables'] ?? []) !== self::TABLES) {
                throw ValidationException::withMessages(['backup' => 'Cette sauvegarde ne correspond pas à la version actuelle de la base.']);
            }
            foreach ($data['files'] as $file => $meta) {
                if (! preg_match('#^rdsa/(media|visuals)/[A-Za-z0-9_.-]+\.(jpg|jpeg|png|webp)$#i', $file)) {
                    throw new \RuntimeException('Chemin invalide.');
                }
                $stat = $zip->statName('uploads/'.$file);
                if (! $stat || $stat['size'] !== $meta['size'] || $stat['size'] > 11 * 1024 * 1024) {
                    throw new \RuntimeException('Fichier invalide.');
                }
                $body = $zip->getFromName('uploads/'.$file);
                if (! hash_equals($meta['sha256'], hash('sha256', $body))) {
                    throw new \RuntimeException('Image corrompue.');
                }
                if (! Storage::disk('public')->put($file, $body)) {
                    throw new \RuntimeException('Impossible de restaurer une image.');
                }
            }
            DB::transaction(function () use ($data) {
                // Ne jamais réexpédier automatiquement un message déjà parti après cette sauvegarde.
                $tables = $data['tables'];
                foreach ($tables['whatsapp_deliveries'] as &$row) {
                    if (in_array($row['status'], ['pending', 'processing', 'failed'])) {
                        $row['status'] = 'uncertain';
                        $row['last_error'] = 'Sauvegarde restaurée : vérifier WhatsApp avant toute relance.';
                    }
                }
                unset($row);
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::table($table)->delete();
                }
                foreach (self::TABLES as $table) {
                    foreach (array_chunk($tables[$table], 50) as $rows) {
                        DB::table($table)->insert($rows);
                    }
                }
            });
        } finally {
            $zip->close();
        }
    }

    public function verify(string $id): void
    {
        $zip = new ZipArchive;
        if ($zip->open($this->path($id)) !== true) {
            throw new \RuntimeException('Archive illisible.');
        }
        try {
            $stat = $zip->statName('snapshot.enc');
            if (! $stat || $stat['size'] > 100 * 1024 * 1024) {
                throw new \RuntimeException('Format invalide.');
            }
            $plain = gzdecode(Crypt::decryptString($zip->getFromName('snapshot.enc')), 100 * 1024 * 1024);
            $data = json_decode($plain ?: '', true, 512, JSON_THROW_ON_ERROR);
            if (($data['version'] ?? null) !== 1 || ($data['schema'] ?? null) !== $this->schema()) {
                throw new \RuntimeException('Version incompatible.');
            }
        } finally {
            $zip->close();
        }
    }
}
