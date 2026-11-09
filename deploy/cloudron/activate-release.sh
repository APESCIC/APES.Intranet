#!/bin/bash
#
# Activate an APES Intranet release on the Cloudron LAMP app. See docs/deploy/cloudron.md.
# Run as root inside the app:
#   bash /app/data/releases/<id>/deploy/cloudron/activate-release.sh /app/data/releases/<id>
#   bash activate-release.sh /app/data/releases/<archive>.tar.gz     (extracts first)
#
# Layout:
#   /app/data/releases/<id>/   one directory per release (newest KEEP_RELEASES kept)
#   /app/data/shared/.env      environment shared by all releases
#   /app/data/shared/storage/  Laravel storage shared by all releases
#   /app/data/current          symlink to the live release (Apache docroot: current/public)
#
# Exit status 0 means the symlink now points at the new release.
# On failure before the switch, the old release stays live but may be in maintenance
# mode; rollback-release.sh <old release> brings it back up.

set -euo pipefail

DATA=/app/data
RELEASES="$DATA/releases"
SHARED="$DATA/shared"
CURRENT="$DATA/current"
KEEP_RELEASES="${KEEP_RELEASES:-5}"

source_path="${1:?Usage: activate-release.sh <release dir | release .tar.gz>}"

if [[ -e "$CURRENT" && ! -L "$CURRENT" ]]; then
    echo "$CURRENT exists and is not a symlink. Move it aside first." >&2
    exit 1
fi
[[ -f "$SHARED/.env" ]] || { echo "Create $SHARED/.env first." >&2; exit 1; }

php_version="$(grep -v '^[[:space:]]*;' "$DATA/PHP_VERSION" 2> /dev/null | grep -oE '[0-9]+\.[0-9]+' | tail -n1 || true)"
if [[ -n "$php_version" ]] && command -v "php$php_version" > /dev/null; then
    PHP_BIN="php$php_version"
else
    PHP_BIN=php
fi

as_www_data() {
    if command -v gosu > /dev/null; then
        gosu www-data "$@"
    else
        sudo -E -u www-data "$@"
    fi
}

if [[ -f "$source_path" && "$source_path" == *.tar.gz ]]; then
    target="$RELEASES/$(basename "$source_path" .tar.gz)-$(date -u +%Y%m%d%H%M%S)"
    echo "==> Extracting $source_path to $target"
    mkdir -p "$target"
    tar -xzf "$source_path" -C "$target"
elif [[ -d "$source_path" ]]; then
    target="$(readlink -f "$source_path")"
else
    echo "Not a release directory or .tar.gz: $source_path" >&2
    exit 1
fi

[[ "$target" == "$RELEASES"/* ]] || { echo "Release must live under $RELEASES" >&2; exit 1; }
[[ -f "$target/artisan" ]] || { echo "No artisan in $target" >&2; exit 1; }
if [[ -L "$CURRENT" && "$(readlink -f "$CURRENT")" == "$target" ]]; then
    echo "$target is already live; refusing to re-activate it in place." >&2
    exit 1
fi

echo "==> Linking shared .env and storage"
mkdir -p "$SHARED"/storage/{app/public,framework/{cache/data,sessions,views},logs}
rm -rf "$target/storage"
ln -s "$SHARED/storage" "$target/storage"
ln -sfn "$SHARED/.env" "$target/.env"
mkdir -p "$target/bootstrap/cache"
chown -R www-data:www-data "$target" "$SHARED/storage"
chown root:www-data "$SHARED/.env"
chmod 0640 "$SHARED/.env"

artisan() {
    as_www_data "$PHP_BIN" "$target/artisan" "$@" --no-interaction
}

if [[ -L "$CURRENT" ]]; then
    echo "==> Entering maintenance mode"
    as_www_data "$PHP_BIN" "$CURRENT/artisan" down --retry=15 --no-interaction || true
fi

echo "==> Preparing release with $PHP_BIN"
artisan package:discover
artisan storage:link --force
artisan migrate --force
artisan db:seed --class='Database\Seeders\RolesAndPermissionsSeeder' --force
# After the core seeder: its syncPermissions drops module grants, and this puts them back.
artisan db:seed --class='Database\Seeders\CommunicationsBootstrapSeeder' --force
# A release-notes problem must never block a deploy: the hub keeps its old entries.
artisan changelog:sync || echo "WARNING: changelog:sync failed; see storage/logs. The deploy carries on." >&2
artisan optimize

echo "==> Switching $CURRENT to $(basename "$target")"
ln -sfn "$target" "$CURRENT.next"
mv -T "$CURRENT.next" "$CURRENT"

echo "==> Restarting queue workers and leaving maintenance mode"
as_www_data "$PHP_BIN" "$CURRENT/artisan" queue:restart --no-interaction
as_www_data "$PHP_BIN" "$CURRENT/artisan" up --no-interaction

echo "==> Pruning old releases (keeping $KEEP_RELEASES)"
live="$(readlink -f "$CURRENT")"
{
    find "$RELEASES" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -rn | cut -d' ' -f2- \
        | grep -vxF "$live" | tail -n +"$KEEP_RELEASES" \
        | while IFS= read -r old; do rm -rf -- "$old"; done
} || true

echo "Activated $(basename "$target"). Restart the app so opcache and run.sh pick it up."
