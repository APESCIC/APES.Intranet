#!/bin/bash
#
# Copy the OIDC client settings into /app/data/shared/.env. See
# docs/cloudron-sign-in.md. Push the settings file and this script to /tmp, then
# run as root inside the app:
#   bash /tmp/set-oidc.sh /tmp/intranet-oidc.env
#
# Only OIDC_ISSUER, OIDC_CLIENT_ID, OIDC_CLIENT_SECRET, OIDC_REDIRECT_URI and
# OIDC_SCOPES lines are accepted; existing lines with those keys are replaced.
# Prints key names only, never values, and deletes the settings file.
# Restart the app afterwards so run.sh re-caches the config.

set -euo pipefail

ENV_FILE="${ENV_FILE:-/app/data/shared/.env}"
input="${1:?Usage: set-oidc.sh <settings file>}"
trap 'rm -f -- "$input"' EXIT

[[ -f "$ENV_FILE" ]] || { echo "Create $ENV_FILE first." >&2; exit 1; }
[[ -f "$input" ]] || { echo "No settings file at $input" >&2; exit 1; }

pattern='^OIDC_(ISSUER|CLIENT_ID|CLIENT_SECRET|REDIRECT_URI|SCOPES)=.+$'
if grep -vE "$pattern" "$input" | tr -d '\r' | grep -qv '^[[:space:]]*$'; then
    echo "The settings file may only contain OIDC_ISSUER, OIDC_CLIENT_ID, OIDC_CLIENT_SECRET, OIDC_REDIRECT_URI and OIDC_SCOPES lines." >&2
    exit 1
fi
for key in OIDC_ISSUER OIDC_CLIENT_ID OIDC_CLIENT_SECRET; do
    grep -q "^$key=." "$input" || { echo "$key is missing from the settings file." >&2; exit 1; }
done

updated="$(mktemp "$ENV_FILE.XXXXXX")"
keys="$(grep -oE '^OIDC_[A-Z_]+' "$input" | sort -u)"
grep -vE "^($(echo "$keys" | paste -sd'|'))=" "$ENV_FILE" > "$updated" || true
tr -d '\r' < "$input" | grep -E "$pattern" >> "$updated"
chown root:www-data "$updated"
chmod 0640 "$updated"
mv -f "$updated" "$ENV_FILE"

echo "Set in $ENV_FILE: $(echo "$keys" | paste -sd' ')"
echo "Restart the app so run.sh re-caches the config."
