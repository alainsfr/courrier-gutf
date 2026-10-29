#!/usr/bin/python3
"""Exécuteur SSH forcé : aucune commande arbitraire, aucun accès au socket Docker."""
import json
import os
from pathlib import Path
import subprocess
import sys

ALLOWED = {'status', 'restart', 'backup', 'request', 'import', 'diagnostic'}


def main():
    action = sys.argv[1] if len(sys.argv) == 2 else ''
    if action not in ALLOWED or os.geteuid() != 0:
        print('Commande non autorisée.', file=sys.stderr)
        return 1
    config_path = Path(__file__).resolve().with_name('target.json')
    config = json.loads(config_path.read_text())
    root = Path(config['directory'])
    if root.is_symlink() or root.stat().st_uid != 0 or root.stat().st_mode & 0o022:
        raise RuntimeError('Installation non protégée.')
    helper = root / 'control.py'
    if helper.is_symlink() or helper.stat().st_uid != 0 or helper.stat().st_mode & 0o022:
        raise RuntimeError('Exécuteur non protégé.')
    subprocess.run(['/usr/bin/logger', '-t', config['audit_tag'], '--', 'start action=' + action], check=True)
    result = subprocess.run(['/usr/bin/python3', str(helper), action], cwd=root)
    subprocess.run(['/usr/bin/logger', '-t', config['audit_tag'], '--', 'end action=' + action + ' exit=' + str(result.returncode)], check=True)
    return result.returncode


if __name__ == '__main__':
    try:
        sys.exit(main())
    except (RuntimeError, OSError, ValueError, KeyError) as error:
        print('Maintenance indisponible : ' + str(error), file=sys.stderr)
        sys.exit(1)
