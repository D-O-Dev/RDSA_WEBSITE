<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDeployment extends Command
{
    protected $signature = 'rdsa:check-deployment';
    protected $description = 'Vérifier la configuration avant mise en ligne, sans modifier les données';

    public function handle(): int
    {
        $checks = [];
        $checks['PHP 8.4.1 minimum pour les dépendances verrouillées'] = version_compare(PHP_VERSION, '8.4.1', '>=');
        $checks['Environnement production'] = app()->environment('production');
        $checks['Affichage des erreurs désactivé'] = !config('app.debug');
        $url = parse_url(config('app.url', ''));
        $checks['Adresse publique HTTPS configurée'] = ($url['scheme'] ?? '') === 'https'
            && !in_array($url['host'] ?? '', ['', 'localhost', '127.0.0.1', '::1']) && !isset($url['user']);
        $checks['Clé applicative présente'] = (bool) config('app.key');
        $checks['Cookies de session sécurisés'] = config('session.secure') === true;
        $checks['Sessions persistantes'] = in_array(config('session.driver'), ['file', 'database', 'redis', 'memcached']);
        $checks['Compilation front présente'] = is_file(public_path('build/manifest.json'));
        $checks['Serveur Vite de développement désactivé'] = !is_file(public_path('hot'));
        $checks['Dossiers Laravel accessibles en écriture'] = is_writable(storage_path()) && is_writable(base_path('bootstrap/cache'));
        try {
            DB::select('select 1'); $checks['Connexion à la base'] = true;
            $migrator = app('migrator');
            $ran = $migrator->getRepository()->getRan();
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $checks['Toutes les migrations appliquées'] = !array_diff(array_keys($files), $ran);
            $checks['Au moins un administrateur actif'] = User::where('is_admin', true)->where('is_active', true)->exists();
        } catch (\Throwable) {
            $checks['Base, migrations et compte administrateur vérifiables'] = false;
        }
        foreach ($checks as $label => $ok) $this->line(($ok ? '[OK] ' : '[À CORRIGER] ') . $label);
        $this->newLine();
        $this->comment('À valider sur l’hébergement : racine web = public, sauvegarde/restauration, fichiers persistants, HTTPS et caméra réelle.');
        $this->comment('Remplacer le mot de passe initial et les images de test. WhatsApp automatique nécessite sa configuration réelle.');
        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
