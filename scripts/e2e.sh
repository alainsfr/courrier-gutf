#!/usr/bin/env bash
# COURRIER GUTF — Tests End-to-End (E2E)
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo ""
echo "══════════════════════════════════════════════"
echo "  COURRIER GUTF — Tests End-to-End (E2E)"
echo "══════════════════════════════════════════════"
echo "  Répertoire : $ROOT"
echo ""

if [[ ! -f vendor/autoload.php ]]; then
  echo "Dépendances manquantes. Exécutez : composer install" >&2
  exit 1
fi

FILTER="${1:-}"
ARGS=(artisan test --testsuite=E2E)
if [[ -n "$FILTER" ]]; then
  ARGS+=(--filter "$FILTER")
fi

echo "Commande : php ${ARGS[*]}"
echo ""

php "${ARGS[@]}"
CODE=$?

echo ""
if [[ $CODE -eq 0 ]]; then
  echo "E2E OK — parcours applicatif validé."
else
  echo "E2E ÉCHEC (code $CODE)."
fi
echo ""

exit $CODE
