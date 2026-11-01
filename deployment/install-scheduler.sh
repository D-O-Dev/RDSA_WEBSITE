#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
command -v crontab >/dev/null
cron_file=$(mktemp)
cron_error=$(mktemp)
trap 'rm -f -- "$cron_file" "$cron_error"' EXIT
if ! crontab -l > "$cron_file" 2> "$cron_error"; then
    if grep -qi 'no crontab' "$cron_error"; then
        : > "$cron_file"
    else
        printf 'Le planificateur doit être configuré depuis le panneau alwaysdata.\n' >&2
        exit 1
    fi
fi
marker='# RDSA Laravel scheduler'
if grep -Fq "$marker" "$cron_file"; then
    printf 'Planificateur RDSA déjà configuré.\n'
    exit 0
fi
printf '\n%s\n%s\n' "$marker" '* * * * * cd /home/rdsa/rdsa && php artisan schedule:run --no-interaction >> /home/rdsa/rdsa/storage/logs/scheduler.log 2>&1' >> "$cron_file"
crontab "$cron_file"
printf 'Planificateur RDSA ajouté ; les autres tâches sont conservées.\n'
