#!/usr/bin/env bash
set -eu
ROOT="$(cd "$(dirname "$0")/../.." && pwd -P)"
FIXTURE="$(mktemp -d)"
trap 'rm -rf -- "$FIXTURE"' EXIT
export CRON_FIXTURE="$FIXTURE"
mkdir -p "$FIXTURE/app/deploy" "$FIXTURE/bin"
touch "$FIXTURE/app/artisan" "$FIXTURE/app/.env"
cp "$ROOT/deploy/run-operations.sh" "$FIXTURE/app/deploy/"
cat > "$FIXTURE/bin/php" <<'PHP'
#!/usr/bin/env bash
exit 0
PHP
cat > "$FIXTURE/bin/crontab" <<'CRON'
#!/usr/bin/env bash
if [ "$1" = -l ]; then
    cat "$CRON_FIXTURE/current"
else
    cp "$1" "$CRON_FIXTURE/current"
fi
CRON
chmod +x "$FIXTURE/bin/"*
export PATH="$FIXTURE/bin:$PATH"
printf '%s\n' '* * * * * /other/application/schedule' '# existing comment' > "$FIXTURE/current"
bash "$ROOT/deploy/install-cron.sh" "$FIXTURE/app" "$FIXTURE/bin/php"
cp "$FIXTURE/current" "$FIXTURE/first"
bash "$ROOT/deploy/install-cron.sh" "$FIXTURE/app" "$FIXTURE/bin/php"
cmp "$FIXTURE/first" "$FIXTURE/current"
grep -Fxq '* * * * * /other/application/schedule' "$FIXTURE/current"
grep -Fxq '# existing comment' "$FIXTURE/current"
test "$(grep -c 'run-operations.sh' "$FIXTURE/current")" -eq 2
# An incomplete managed block must never erase other cron entries.
printf '%s\n' "# BEGIN COURRIER-GUTF $FIXTURE/app" >> "$FIXTURE/current"
cp "$FIXTURE/current" "$FIXTURE/broken"
if bash "$ROOT/deploy/install-cron.sh" "$FIXTURE/app" "$FIXTURE/bin/php"; then
    echo 'Expected refusal for incomplete block' >&2
    exit 1
fi
cmp "$FIXTURE/broken" "$FIXTURE/current"
# Lock contention must skip a second runner before executing PHP.
exec 7>"$FIXTURE/app/storage/framework/operations/queue.lock"
flock 7
bash "$FIXTURE/app/deploy/run-operations.sh" queue "$FIXTURE/bin/php"
echo 'PASS: cron preservation, idempotency, malformed-block refusal and worker lock'
