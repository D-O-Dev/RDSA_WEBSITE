#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
sha=${1:?SHA requis}; run=${2:?Numéro de workflow requis}; attempt=${3:?Tentative requise}; expected=${4:?SHA256 de livraison requis}
[[ $sha =~ ^[a-f0-9]{40}$ && $run =~ ^[0-9]+$ && $attempt =~ ^[0-9]+$ && $expected =~ ^[a-f0-9]{64}$ ]] || exit 2
state=${RDSA_DEPLOY_STATE:-/home/rdsa/rdsa-private/ci}
archive="$state/incoming/$sha-$run-$attempt.tar.gz"
work=$(mktemp -d "$state/work.XXXXXX")
trap 'rm -rf -- "$work"; rm -f -- "$archive"' EXIT
printf '%s  %s\n' "$expected" "$archive" | sha256sum -c -
tar -tzf "$archive" | LC_ALL=C awk '/^\// || /(^|\/)\.\.(\/|$)/ {bad=1} END {exit bad}'
mkdir "$work/release"
tar --no-same-owner --no-same-permissions -xzf "$archive" -C "$work/release"
bash "$work/release/deployment/deploy-release.sh" "$work/release" "$sha" "$run"
