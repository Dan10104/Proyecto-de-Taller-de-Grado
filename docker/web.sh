#!/usr/bin/env bash
set -euo pipefail
# Reports live on THIS instance. A separate Cloud Run job cannot sweep its disk.
# Cloud template allocates CPU while idle so the local TTL sweep keeps running.
php artisan reportes:limpiar-temporales --no-interaction >/dev/null
(
    child=''
    trap 'if [[ -n "$child" ]]; then kill -TERM "$child" 2>/dev/null || true; wait "$child" 2>/dev/null || true; fi; exit 0' TERM INT
    while true; do
        sleep 30 & child=$!; wait "$child" || exit 0
        php artisan reportes:limpiar-temporales --no-interaction >/dev/null & child=$!
        wait "$child" || echo 'reports.cleanup_pending' >&2
        child=''
    done
) & janitor=$!
apache2-foreground & server=$!
stop() {
    kill -TERM "$janitor" 2>/dev/null || true
    kill -WINCH "$server" 2>/dev/null || true
    wait "$server" 2>/dev/null || true
    wait "$janitor" 2>/dev/null || true
    exit 0
}
trap stop TERM INT
set +e
wait "$server"; result=$?
kill -TERM "$janitor" 2>/dev/null || true
wait "$janitor" 2>/dev/null || true
exit "$result"
