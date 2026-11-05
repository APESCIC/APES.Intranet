#!/bin/bash
#
# Set APP_KEY in /app/data/shared/.env once, during first-time set-up. See
# docs/deploy/cloudron.md. Push it to /tmp, run it as root inside the app, then
# delete it:
#   bash /tmp/set-app-key.sh
#
# The key is generated on the server and never printed. Refuses to replace an
# existing key: without the old key, encrypted data cannot be read.

set -euo pipefail

ENV_FILE=/app/data/shared/.env

[[ -f "$ENV_FILE" ]] || { echo "Create $ENV_FILE first." >&2; exit 1; }
if grep -q '^APP_KEY=base64:' "$ENV_FILE"; then
    echo "APP_KEY is already set; not changing it." >&2
    exit 1
fi

key="base64:$(head -c 32 /dev/urandom | base64)"
if grep -q '^APP_KEY=' "$ENV_FILE"; then
    sed -i "s|^APP_KEY=.*|APP_KEY=$key|" "$ENV_FILE"
else
    echo "APP_KEY=$key" >> "$ENV_FILE"
fi
chown root:www-data "$ENV_FILE"
chmod 0640 "$ENV_FILE"
echo "APP_KEY set in $ENV_FILE"
