#!/bin/bash
#
# Point /app/data/current back at an earlier release and bring it out of maintenance.
# Ship as deploy/cloudron/rollback-release.sh; run as root inside the app:
#   bash rollback-release.sh <release dir>    roll back to that release (preferred: pass the
#                                             release captured before the deploy)
#   bash rollback-release.sh                  newest release that is not live
#
# Code only: database migrations are NOT undone. If the newer release ran migrations the
# older code cannot handle, run `artisan migrate:rollback --step=<n> --force` from the
# newer release first, or restore the pre-deploy Cloudron backup.

set -euo pipefail

DATA=/app/data
RELEASES="$DATA/releases"
CURRENT="$DATA/current"

live=""
[[ -L "$CURRENT" ]] && live="$(readlink -f "$CURRENT")"

if [[ $# -ge 1 && -n "$1" ]]; then
    target="$(readlink -f "$1")"
else
    target="$(find "$RELEASES" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -rn | cut -d' ' -f2- | grep -vxF "$live" | head -n1 || true)"
fi

[[ -n "$target" && "$target" == "$RELEASES"/* && -f "$target/artisan" ]] || { echo "No valid release to roll back to." >&2; exit 1; }

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

if [[ "$live" == "$target" ]]; then
    echo "==> $(basename "$target") is already live (failure happened before the switch)"
else
    echo "==> Rolling back from $(basename "${live:-none}") to $(basename "$target")"
    as_www_data "$PHP_BIN" "$target/artisan" optimize --no-interaction
    ln -sfn "$target" "$CURRENT.next"
    mv -T "$CURRENT.next" "$CURRENT"
    as_www_data "$PHP_BIN" "$CURRENT/artisan" queue:restart --no-interaction
fi

as_www_data "$PHP_BIN" "$CURRENT/artisan" up --no-interaction || true
echo "Rolled back to $(basename "$target"). Restart the app."
