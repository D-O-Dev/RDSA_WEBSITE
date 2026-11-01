<?php
// Usage : php backup-database.php /chemin/application /chemin/prive/database.sql
umask(0077);
$root = $argv[1] ?? '';
$destination = $argv[2] ?? '';
if (!is_file($root.'/artisan') || !is_dir(dirname($destination)) || file_exists($destination)) {
    fwrite(STDERR, "Destination de sauvegarde invalide.\n"); exit(1);
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$options = dirname($destination).'/mysql-client.cnf';
$failed = false;
try {
    if (config('database.default') !== 'mysql') throw new RuntimeException('Une connexion MySQL est requise.');
    $connection = config('database.connections.mysql');
    $quote = fn($value) => '"'.str_replace(['\\','"',"\n","\r"], ['\\\\','\\"','\\n','\\r'], (string)$value).'"';
    $content = "[client]\n";
    foreach (['host'=>'host','port'=>'port','user'=>'username','password'=>'password'] as $key=>$field) {
        $content .= $key.'='.$quote($connection[$field] ?? '')."\n";
    }
    file_put_contents($options, $content); chmod($options, 0600);
    $binary = (new Symfony\Component\Process\ExecutableFinder)->find('mysqldump');
    if (!$binary) throw new RuntimeException('mysqldump est requis.');
    $process = new Symfony\Component\Process\Process([$binary, '--defaults-extra-file='.$options,
        '--single-transaction', '--quick', '--skip-lock-tables', '--no-tablespaces',
        '--result-file='.$destination, $connection['database']]);
    $process->setTimeout(120); $process->run();
    if (!$process->isSuccessful() || !is_file($destination) || filesize($destination) < 1000) {
        throw new RuntimeException('La sauvegarde SQL a échoué ; déploiement interrompu.');
    }
    chmod($destination, 0600);
    echo "Sauvegarde SQL privée vérifiée.\n";
} catch (Throwable $e) {
    file_put_contents(dirname($destination).'/backup-error.log', (string)$e);
    fwrite(STDERR, "Sauvegarde impossible : voir le journal privé sur le serveur.\n"); $failed = true;
} finally {
    if (is_file($options)) unlink($options);
}

if ($failed) exit(1);
