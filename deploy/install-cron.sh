#!/usr/bin/env bash
set -eu
umask 077
APP_DIR="$(cd "${1:?application directory required}" && pwd -P)"
PHP_BIN="$(command -v "${2:-php}")"
case "$APP_DIR:$PHP_BIN" in *[!a-zA-Z0-9_./:-]*) echo 'Unsupported cron path' >&2; exit 1 ;; esac
test "$APP_DIR" != / && test -x "$PHP_BIN"
cd "$APP_DIR"
test -f artisan && test -f .env && test -f deploy/run-operations.sh
"$PHP_BIN" -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit($app->environment("production") ? 0 : 1);'
command -v crontab >/dev/null
command -v flock >/dev/null
mkdir -p storage/framework/operations storage/logs
exec 8>storage/framework/operations/install-cron.lock
flock 8
CURRENT="$(mktemp)"
NEXT="$(mktemp)"
ERROR="$(mktemp)"
trap 'rm -f -- "$CURRENT" "$NEXT" "$ERROR"' EXIT
if ! crontab -l > "$CURRENT" 2> "$ERROR"; then
    if ! grep -qi 'no crontab for' "$ERROR"; then
        echo 'Cannot read existing cron table; no change made' >&2
        exit 1
    fi
fi
BEGIN="# BEGIN COURRIER-GUTF $APP_DIR"
END="# END COURRIER-GUTF $APP_DIR"
# Ne retirer que notre bloc ; conserver les tâches des autres applications.
awk -v begin="$BEGIN" -v end="$END" '
    $0 == begin { if (inside) exit 2; inside=1; next }
    $0 == end { if (!inside) exit 2; inside=0; next }
    !inside { print }
    END { if (inside) exit 2 }
' "$CURRENT" > "$NEXT"
{
    printf '%s\n' "$BEGIN"
    printf "* * * * * /bin/bash '%s/deploy/run-operations.sh' scheduler '%s' >> '%s/storage/logs/cron-bootstrap.log' 2>&1\n" "$APP_DIR" "$PHP_BIN" "$APP_DIR"
    printf "* * * * * /bin/bash '%s/deploy/run-operations.sh' queue '%s' >> '%s/storage/logs/cron-bootstrap.log' 2>&1\n" "$APP_DIR" "$PHP_BIN" "$APP_DIR"
    printf '%s\n' "$END"
} >> "$NEXT"
if ! cmp -s "$CURRENT" "$NEXT"; then
    crontab "$NEXT"
fi
echo 'COURRIER_CRON_INSTALLED'
