#!/usr/bin/env python3
"""Commandes locales ciblées sur cette installation uniquement."""
import argparse
import fcntl
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile


def main():
    parser = argparse.ArgumentParser(description='Exploitation COURRIER GUTF')
    parser.add_argument('action', choices=['status', 'restart', 'stop', 'start', 'backup', 'request', 'import', 'diagnostic'])
    parser.add_argument('--file')
    args = parser.parse_args()
    root = Path(__file__).resolve().parent
    if os.geteuid() != 0 or root.stat().st_uid != 0 or root.stat().st_mode & 0o022:
        raise RuntimeError('Dossier protégé appartenant à root requis. Utiliser sudo.')
    for name in ['compose.yaml', '.env', 'app.env', 'db.env', 'control.py']:
        file = root / name
        if file.is_symlink() or file.stat().st_uid != 0 or file.stat().st_mode & 0o022:
            raise RuntimeError('Fichier de configuration non protégé : ' + name)
    command = ['docker', 'compose', '--project-directory', str(root), '--env-file', str(root / '.env'), '-f', str(root / 'compose.yaml')]
    def execute(*tail, **kwargs):
        return subprocess.run([*command, *tail], check=True, **kwargs)
    with (root / '.operations.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        if args.action == 'status':
            execute('ps')
            execute('exec', '-T', 'app', 'php', 'artisan', 'app:operations-status')
        elif args.action == 'diagnostic':
            execute('exec', '-T', 'app', 'php', '-r', 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo json_encode($app->make(App\\Services\\ManagedRuntime::class)->inspect(), JSON_PRETTY_PRINT).PHP_EOL;')
        elif args.action == 'backup':
            execute('exec', '-T', 'app', 'php', 'artisan', 'backup:run', '--disable-notifications')
        elif args.action == 'restart':
            execute('restart', 'app', 'queue', 'scheduler')
        elif args.action == 'stop':
            execute('stop')
        elif args.action == 'start':
            execute('up', '-d', '--wait')
        elif args.action == 'request':
            with tempfile.NamedTemporaryFile(prefix='request-', dir='/tmp') as temporary:
                container_path = '/tmp/request-' + os.urandom(8).hex() + '.json'
                try:
                    execute('exec', '-T', 'app', 'php', 'artisan', 'app:installation-request', container_path, stdout=subprocess.DEVNULL)
                    execute('exec', '-T', 'app', 'cat', container_path, stdout=temporary)
                    temporary.flush()
                    data = json.loads(Path(temporary.name).read_text())
                    print(json.dumps(data, indent=2))
                finally:
                    execute('exec', '-T', 'app', 'rm', '-f', container_path, stdout=subprocess.DEVNULL)
        elif args.action == 'import':
            if args.file:
                path = Path(args.file)
                if not path.is_file() or path.stat().st_size > 16384:
                    raise RuntimeError('Fichier de configuration invalide.')
                document = path.read_bytes()
            else:
                document = sys.stdin.buffer.read(16385)
            if not document or len(document) > 16384:
                raise RuntimeError('Fichier de configuration invalide.')
            container_path = '/tmp/configuration-' + os.urandom(8).hex() + '.json'
            try:
                execute('exec', '-T', 'app', 'php', '-r', '$b=stream_get_contents(STDIN,16385); if(strlen($b)>16384){exit(1);} if(file_put_contents($argv[1],$b,LOCK_EX)!==strlen($b)){exit(1);} chmod($argv[1],0600);', container_path, input=document)
                execute('exec', '-T', 'app', 'php', 'artisan', 'app:installation-import', container_path)
            finally:
                execute('exec', '-T', 'app', 'rm', '-f', container_path, stdout=subprocess.DEVNULL)
    return 0


if __name__ == '__main__':
    try:
        sys.exit(main())
    except (RuntimeError, OSError, ValueError, subprocess.CalledProcessError) as error:
        print('Intervention interrompue : ' + str(error), file=sys.stderr)
        sys.exit(1)
