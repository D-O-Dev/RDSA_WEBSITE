#!/usr/bin/env bash
set -Eeuo pipefail
archive=${1:?Chemin de l’archive requis}
root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
cd "$root"
php -r '$v=json_decode(file_get_contents("vendor/composer/installed.json"),true,512,JSON_THROW_ON_ERROR); if ($v["dev"]??true) {fwrite(STDERR,"Installer les dépendances sans --dev avant de préparer la livraison.\n");exit(1);}'
test -f public/build/manifest.json
mkdir -p -- "$(dirname -- "$archive")"
tar -czf "$archive" \
    --exclude='bootstrap/cache/*' \
    --exclude='public/storage' --exclude='public/hot' --exclude='public/images/videos' \
    --exclude='deployment/*.tar.gz' --exclude='deployment/*.sha256' \
    app bootstrap config database/migrations database/seeders database/factories \
    public resources routes lang vendor artisan composer.json composer.lock deployment README.md
printf 'Livraison préparée.\n'
