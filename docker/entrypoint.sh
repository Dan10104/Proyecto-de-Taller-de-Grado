#!/usr/bin/env bash
set -euo pipefail
umask 077
case "${PORT:-8080}" in ''|*[!0-9]*) echo 'Invalid PORT.' >&2; exit 64;; esac
if (( PORT < 1024 || PORT > 65535 )); then echo 'PORT must be an unprivileged TCP port.' >&2; exit 64; fi
mkdir -p storage/app/private/reports storage/framework/{cache/data,cache/locks,sessions,views} storage/logs bootstrap/cache
# Configuration is generated ONLY at runtime, with the current injected secrets.
rm -f bootstrap/cache/config.php
php artisan deployment:check --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
if [[ "${1:-web}" == web ]]; then exec /usr/local/bin/web.sh; fi
exec "$@"
