#!/bin/bash
#
# APES Intranet startup script for the Cloudron LAMP app. Install as
# /app/data/run.sh (root:root, 0755). See docs/deploy/cloudron.md.
#
# The LAMP image's start.sh *sources* this file under `set -eu` with readonly
# variables such as `php_version`, before starting Apache. Everything therefore
# runs inside a ( ... ) subshell with `set +e`: no failure, `exit`, or variable
# assignment here can stop start.sh. Keep all new code inside the subshell, and
# never block: long-running processes go to the background.

(
    set +e
    set -u

    APP_DIR="${APP_DIR:-/app/data/current}"
    PHP_VERSION_FILE=/app/data/PHP_VERSION

    # The CLI `php` is the image default; use the version the web server runs.
    cli_php_version="$(grep -v '^[[:space:]]*;' "$PHP_VERSION_FILE" 2> /dev/null | grep -oE '[0-9]+\.[0-9]+' | tail -n1)"
    if [[ -n "$cli_php_version" ]] && command -v "php$cli_php_version" > /dev/null; then
        PHP_BIN="php$cli_php_version"
    else
        PHP_BIN=php
    fi

    # Keep the environment so CLOUDRON_* addon variables reach the app.
    as_www_data() {
        if command -v gosu > /dev/null; then
            gosu www-data "$@"
        else
            sudo -E -u www-data "$@"
        fi
    }

    log() {
        echo "[run.sh $(date -u +%H:%M:%S)] $*"
    }

    if [[ ! -f "$APP_DIR/artisan" ]]; then
        log "No app at $APP_DIR yet; skipping workers. Deploy a release first."
        exit 0
    fi

    # Addon credentials can rotate between starts, so rebuild the config cache every time.
    log "Caching configuration with $PHP_BIN"
    as_www_data "$PHP_BIN" "$APP_DIR/artisan" config:cache --no-interaction || log "config:cache failed"

    supervise() {
        local name="$1"
        shift
        (
            while true; do
                log "Starting $name"
                as_www_data "$PHP_BIN" "$APP_DIR/artisan" "$@"
                log "$name exited with status $?, restarting in 5 seconds"
                sleep 5
            done
        ) &
    }

    # Remove either line if the app has no queue or no scheduled tasks.
    supervise "queue worker" queue:work --sleep=3 --tries=3 --max-time=3600 --no-interaction
    supervise "scheduler" schedule:work --no-interaction

    log "Queue worker and scheduler started"
)
