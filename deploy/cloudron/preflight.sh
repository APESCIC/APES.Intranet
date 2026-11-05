#!/bin/bash
#
# Read-only check of the APES Intranet Cloudron app, run by preflight.ps1.
# Run as root inside the app:
#   bash preflight.sh <sha256 of the repo's deploy/cloudron/run.sh>
#
# Prints one PASS, WARN or FAIL line per check. Never prints a value from .env
# or an addon variable: values are only tested with grep -q.
# Exit status 1 means at least one FAIL.

set -uo pipefail

DATA=/app/data
ENV_FILE="$DATA/shared/.env"
expected_run_sha="${1:-}"
fails=0

pass() { echo "PASS $*"; }
warn() { echo "WARN $*"; }
fail() {
    echo "FAIL $*"
    fails=$((fails + 1))
}

if [[ -f "$ENV_FILE" ]]; then
    # The LAMP start.sh runs chown -R www-data:www-data /app/data on every start,
    # so root:www-data from activate-release.sh only lasts until the next restart.
    owner_mode="$(stat -c '%U:%G %a' "$ENV_FILE")"
    case "$owner_mode" in
        "root:www-data 640" | "www-data:www-data 640")
            pass "shared/.env is $owner_mode"
            ;;
        *)
            fail "shared/.env is $owner_mode, expected mode 640 and group www-data"
            ;;
    esac
    if grep -q '^APP_KEY=base64:' "$ENV_FILE"; then
        pass "shared/.env has APP_KEY=base64:"
    else
        fail "shared/.env has no APP_KEY=base64: line"
    fi
    # Addons supply these. A copy in .env would go stale after a rotation or restore.
    copied=""
    for key in DB_PASSWORD REDIS_PASSWORD MAIL_PASSWORD LDAP_BIND_PASSWORD; do
        if grep -qE "^${key}=.+" "$ENV_FILE" && ! grep -qE "^${key}=(null|\"\")[[:space:]]*$" "$ENV_FILE"; then
            copied+=" $key"
        fi
    done
    if [[ -z "$copied" ]]; then
        pass "shared/.env holds no addon passwords"
    else
        fail "shared/.env sets$copied; remove them, the Cloudron addons supply them"
    fi
else
    fail "$ENV_FILE is missing (first-time set-up step 3)"
fi

run_sh="$DATA/run.sh"
if [[ -f "$run_sh" ]]; then
    if [[ -x "$run_sh" ]]; then pass "run.sh is executable"; else fail "run.sh is not executable"; fi
    if bash -n "$run_sh" 2> /dev/null; then pass "run.sh passes bash -n"; else fail "run.sh fails bash -n"; fi
    actual_run_sha="$(sha256sum "$run_sh" | cut -d' ' -f1)"
    if [[ -z "$expected_run_sha" ]]; then
        warn "run.sh not compared: no checksum given"
    elif [[ "$actual_run_sha" == "$expected_run_sha" ]]; then
        pass "run.sh matches deploy/cloudron/run.sh"
    else
        fail "run.sh differs from deploy/cloudron/run.sh (first-time set-up step 4)"
    fi
else
    fail "$run_sh is missing (first-time set-up step 4)"
fi

app_conf="$DATA/apache/app.conf"
if [[ -f "$app_conf" ]] && grep -q 'DocumentRoot /app/data/current/public' "$app_conf"; then
    pass "apache/app.conf serves /app/data/current/public"
else
    warn "apache/app.conf does not serve /app/data/current/public, so the holding page shows"
fi

if [[ -d "$DATA/releases" ]]; then
    releases="$(find "$DATA/releases" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %f\n' | sort -rn | cut -d' ' -f2 | tr '\n' ' ')"
    releases="${releases% }"
    pass "releases (newest first): ${releases:-none}"
    while IFS= read -r release; do
        [[ -f "$release/artisan" ]] || warn "$(basename "$release") has no artisan: a nested or partial upload"
    done < <(find "$DATA/releases" -mindepth 1 -maxdepth 1 -type d)
else
    fail "$DATA/releases is missing (first-time set-up step 3)"
fi

if [[ -L "$DATA/current" ]]; then
    current="$(readlink -f "$DATA/current")"
    if [[ -f "$current/artisan" ]]; then
        pass "current is $(basename "$current")"
    else
        fail "current points at $current, which has no artisan"
    fi
elif [[ -e "$DATA/current" ]]; then
    fail "$DATA/current exists but is not a symlink"
else
    warn "no current release yet"
fi

for addon in MYSQL REDIS MAIL LDAP; do
    if env | grep -q "^CLOUDRON_${addon}_"; then
        pass "CLOUDRON_${addon}_* variables exist"
    else
        fail "no CLOUDRON_${addon}_* variables"
    fi
done

oidc_missing=""
for key in ISSUER CLIENT_ID CLIENT_SECRET; do
    if ! env | grep -q "^CLOUDRON_OIDC_${key}=." && ! grep -q "^OIDC_${key}=." "$ENV_FILE" 2> /dev/null; then
        oidc_missing+=" $key"
    fi
done
if [[ -z "$oidc_missing" ]]; then
    pass "OIDC issuer, client id and client secret are set"
else
    warn "OIDC not set up, missing:$oidc_missing. Sign-in cannot work (docs/cloudron-sign-in.md)"
fi

php_version="$(grep -v '^[[:space:]]*;' "$DATA/PHP_VERSION" 2> /dev/null | grep -oE '[0-9]+\.[0-9]+' | tail -n1 || true)"
if [[ -z "$php_version" ]]; then
    fail "$DATA/PHP_VERSION is missing or has no version"
elif [[ "$(printf '%s\n' 8.3 "$php_version" | sort -V | head -n1)" != "8.3" ]]; then
    fail "PHP_VERSION is $php_version, expected 8.3 or newer"
else
    pass "PHP_VERSION is $php_version"
    if command -v "php$php_version" > /dev/null; then pass "php$php_version exists"; else fail "php$php_version is not installed"; fi
fi

if command -v gosu > /dev/null; then pass "gosu exists"; else fail "gosu is not installed"; fi

[[ "$fails" -eq 0 ]] || exit 1
exit 0
