#!/usr/bin/env bash
set -eu
umask 077
APP_DIR="$(cd "$(dirname "$0")/.." && pwd -P)"
MODE="${1:?scheduler or queue required}"
PHP_BIN="${2:?absolute PHP path required}"
case "$MODE" in scheduler|queue) ;; *) exit 2 ;; esac
test -x "$PHP_BIN"
cd "$APP_DIR"
test -f artisan && test -f .env
mkdir -p storage/framework/operations storage/logs
# Verrou persistant, conservé entre deux déploiements.
exec 9>"storage/framework/operations/$MODE.lock"
flock -n 9 || exit 0
LOG="storage/logs/operations-$(date -u +%Y-%m-%d).log"
EXIT_CODE=0
if [ "$MODE" = scheduler ]; then
    find storage/logs -maxdepth 1 -type f \( -name 'operations-????-??-??.log' -o -name 'scheduler-????-??-??.log' \) -mtime +14 -delete
    "$PHP_BIN" artisan schedule:run --no-ansi 9>&- >> "$LOG" 2>&1 || EXIT_CODE=$?
else
    "$PHP_BIN" artisan queue:work --stop-when-empty --max-time=50 --timeout=45 --tries=3 --backoff=60 --no-ansi 9>&- >> "$LOG" 2>&1 || EXIT_CODE=$?
fi
"$PHP_BIN" -r '
    $path = $argv[1];
    $data = json_encode(["completed_at" => gmdate("c"), "exit_code" => (int) $argv[2]], JSON_THROW_ON_ERROR);
    if (file_put_contents($path.".tmp", $data, LOCK_EX) === false || !rename($path.".tmp", $path)) { exit(1); }
' "storage/framework/operations/$MODE-runner.json" "$EXIT_CODE"
exit "$EXIT_CODE"
