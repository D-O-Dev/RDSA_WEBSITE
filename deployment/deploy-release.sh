#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
release=${1:?Dossier de livraison requis}
sha=${2:?SHA Git requis}
run=${3:?Numéro de workflow requis}
target=${RDSA_DEPLOY_ROOT:-/home/rdsa/rdsa}
state=${RDSA_DEPLOY_STATE:-/home/rdsa/rdsa-private/ci}
url=${RDSA_DEPLOY_URL:-https://rdsa.alwaysdata.net}
[[ $sha =~ ^[a-f0-9]{40}$ && $run =~ ^[0-9]+$ ]] || exit 2
[[ -f $target/artisan && -f $target/.env && -d $target/storage && -f $release/public/build/manifest.json && -f $release/vendor/autoload.php ]] || exit 2
[[ $(realpath "$release") != $(realpath "$target") ]] || exit 2
mkdir -p "$state"
exec 9>"$state/deploy.lock"
flock -w 600 9
if [[ -f $state/last-run && $run -lt $(cat "$state/last-run") ]]; then
    printf 'Workflow ancien ignoré : une version plus récente est déjà déployée.\n'; exit 0
fi
if [[ -f $target/storage/framework/down ]]; then
    printf "Le site est déjà en maintenance : vérifier son état avant un nouveau déploiement.\n" >&2; exit 1
fi
backup=$(mktemp -d "$state/backup.XXXXXX")
mutated=0
maintenance=0
migration_started=0
excludes=(--exclude='/.env' --exclude='/storage/' --exclude='/public/storage' --exclude='/public/.well-known/' --exclude='/bootstrap/cache/')
cleanup() {
    status=$?
    trap - EXIT
    if (( status != 0 )); then
        set +e
        if (( mutated )); then
            mkdir -p "$backup/restore"
            tar -xf "$backup/code.tar" -C "$backup/restore"
            rsync -ac --delete "${excludes[@]}" "$backup/restore/" "$target/"
            (cd "$target" && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan config:cache)
        fi
        if (( maintenance && ! migration_started )); then
            (cd "$target" && php artisan up)
        elif (( maintenance )); then
            (cd "$target" && php artisan down --retry=60)
            printf 'Échec après le début des migrations : maintenance conservée. Aucune restauration automatique de la base.\n' >&2
        fi
        rm -rf -- "$state/failed"
        mv -- "$backup" "$state/failed"
        printf 'Livraison échouée ; sauvegarde privée : %s/failed\n' "$state" >&2
    fi
    exit "$status"
}
trap cleanup EXIT
cd "$target"
env_hash=$(sha256sum .env | cut -d ' ' -f 1)
php artisan down --retry=60
maintenance=1
php "$release/deployment/backup-database.php" "$target" "$backup/database.sql"
cp -p .env "$backup/environment.env"
tar -cf "$backup/code.tar" --exclude='./.env' --exclude='./storage' --exclude='./public/storage' --exclude='./public/.well-known' --exclude='./bootstrap/cache' .
mutated=1
rsync -ac --delete-delay "${excludes[@]}" "$release/" "$target/"
[[ $(sha256sum .env | cut -d ' ' -f 1) == "$env_hash" ]]
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan package:discover --ansi
migration_started=1
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan rdsa:check-deployment
php artisan up
curl --fail --silent --show-error --retry 3 --retry-delay 2 --max-time 30 "$url/health" > "$backup/health.json"
php -r '$data=json_decode(file_get_contents($argv[1]),true);exit(($data["status"]??null)==="ok"?0:1);' "$backup/health.json"
curl --fail --silent --show-error --retry 2 --retry-delay 2 --max-time 30 "$url/" -o /dev/null
curl --fail --silent --show-error --retry 2 --retry-delay 2 --max-time 30 "$url/admin/login" -o /dev/null
[[ $(sha256sum .env | cut -d ' ' -f 1) == "$env_hash" ]]
printf '%s\n' "$run" > "$state/last-run"
printf '{"sha":"%s","run":%s,"deployed_at":"%s"}\n' "$sha" "$run" "$(date -u +%FT%TZ)" > "$state/last-success.json"
rm -rf -- "$state/previous"
mv -- "$backup" "$state/previous"
trap - EXIT
printf 'Déploiement terminé : %s\n' "$sha"
