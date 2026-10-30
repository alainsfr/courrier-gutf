#!/usr/bin/env bash
set -u
while true; do
    bash deploy/run-operations.sh scheduler /usr/local/bin/php
    # Revenir au début de minute, sans dérive liée au temps de la tâche précédente.
    sleep "$((60 - $(date -u +%s) % 60))"
done
