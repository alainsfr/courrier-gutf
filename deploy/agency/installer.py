#!/usr/bin/env python3
"""Installation locale isolée. Bibliothèque standard Python uniquement."""
import argparse
import getpass
import hashlib
import ipaddress
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import socket
import subprocess
import sys
from urllib.parse import urlsplit

try:
    import fcntl
except ImportError:
    fcntl = None


def require(condition, message):
    if not condition:
        raise RuntimeError(message)


def run(arguments, **kwargs):
    return subprocess.run(arguments, check=True, **kwargs)


def verify_bundle(bundle):
    checksums = json.loads((bundle / 'checksums.json').read_text(encoding='utf-8'))
    require(isinstance(checksums, dict) and checksums, 'Sommes de contrôle absentes.')
    for relative, expected in checksums.items():
        path = bundle / relative
        require(not path.is_symlink() and path.resolve().is_relative_to(bundle.resolve()), 'Chemin de paquet invalide.')
        require(path.is_file(), 'Fichier manquant : ' + relative)
        digest = hashlib.sha256()
        with path.open('rb') as stream:
            for block in iter(lambda: stream.read(1024 * 1024), b''):
                digest.update(block)
        require(digest.hexdigest() == expected, 'Fichier altéré : ' + relative)
    for required in ['compose.yaml', 'images.tar.gz', 'release.json', 'control.py', 'install-linux.sh']:
        require(required in checksums, 'Paquet incomplet : ' + required)
    return json.loads((bundle / 'release.json').read_text(encoding='utf-8'))


def validate_options(args):
    require(re.fullmatch(r'[a-z][a-z0-9-]{2,29}', args.instance) is not None, 'Identifiant : 3 à 30 lettres minuscules, chiffres ou tirets.')
    require(1024 <= args.port <= 65535, 'Port attendu entre 1024 et 65535.')
    ipaddress.IPv4Address(args.bind)
    url = urlsplit(args.url)
    require(url.scheme in ('http', 'https') and url.hostname and not url.username and not url.password
            and url.path in ('', '/') and not url.query and not url.fragment
            and re.fullmatch(r'https?://[a-zA-Z0-9.:-]+/?', args.url), 'URL complète sans sous-dossier attendue.')
    require(url.port is None or 1 <= url.port <= 65535, 'Port URL invalide.')
    require(re.fullmatch(r'[^\s@]+@[^\s@]+\.[^\s@]+', args.admin_email), 'Adresse administrateur invalide.')


def write_private(path, contents):
    descriptor = os.open(path, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    with os.fdopen(descriptor, 'w', encoding='utf-8', newline='\n') as stream:
        stream.write(contents)


def compose(root, *arguments, **kwargs):
    return run(['docker', 'compose', '--project-directory', str(root), '--env-file', str(root / '.env'),
                '-f', str(root / 'compose.yaml'), *arguments], **kwargs)


def install(args, bundle):
    validate_options(args)
    require(sys.platform.startswith('linux') and os.geteuid() == 0, 'Lancer avec sudo sur Linux ou dans la VM Linux.')
    release = verify_bundle(bundle)
    require(os.uname().machine == 'x86_64', 'Ce paquet cible Linux x86_64 / amd64.')
    require(Path('/etc/machine-id').is_file() and re.fullmatch(r'[a-fA-F0-9]{32}', Path('/etc/machine-id').read_text().strip()), 'Identité du système Linux indisponible.')
    require(shutil.which('docker'), 'Docker Engine et Docker Compose doivent être installés auparavant.')
    version = run(['docker', 'compose', 'version', '--short'], capture_output=True, text=True).stdout.strip().lstrip('v')
    require(tuple(map(int, version.split('-')[0].split('.')[:2])) >= (2, 30), 'Docker Compose 2.30 minimum requis.')
    run(['docker', 'info'], stdout=subprocess.DEVNULL)
    with socket.socket() as probe:
        probe.bind((args.bind, args.port))
    root = Path(args.directory or '/opt/courrier-gutf/' + args.instance).absolute()
    require(not root.is_symlink() and root.resolve() == root and len(root.parts) >= 4, 'Choisir un dossier dédié, sans lien symbolique.')
    require(not root.exists() or not any(root.iterdir()), 'Dossier déjà utilisé. Aucun fichier existant ne sera remplacé.')
    project = 'gutf-' + args.instance
    existing = run(['docker', 'ps', '-aq', '--filter', 'label=com.docker.compose.project=' + project], capture_output=True, text=True).stdout.strip()
    volumes = run(['docker', 'volume', 'ls', '-q', '--filter', 'label=com.docker.compose.project=' + project], capture_output=True, text=True).stdout.strip()
    require(not existing and not volumes, 'Identifiant déjà utilisé par une installation ou ses données persistantes.')
    password = Path(args.admin_password_file).read_text(encoding='utf-8').rstrip('\r\n') if args.admin_password_file else getpass.getpass('Mot de passe du premier administrateur (12 caractères minimum) : ')
    if not args.admin_password_file:
        require(password == getpass.getpass('Confirmer le mot de passe : '), 'Les mots de passe ne correspondent pas.')
    require(12 <= len(password) <= 128 and not any(ord(char) < 32 or ord(char) == 127 for char in password), 'Mot de passe invalide.')
    root.mkdir(mode=0o700, parents=True, exist_ok=True)
    os.chmod(root, 0o700)
    with (root / '.install.lock').open('x') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        for filename in ['compose.yaml', 'control.py', 'release.json']:
            shutil.copyfile(bundle / filename, root / filename)
            os.chmod(root / filename, 0o600)
        app_key = __import__('base64').b64encode(secrets.token_bytes(32)).decode('ascii')
        database_password = secrets.token_hex(32)
        write_private(root / '.env', f'COMPOSE_PROJECT_NAME={project}\nGUTF_IMAGE={release["app_image"]}\nGUTF_DATABASE_IMAGE={release["database_image"]}\nGUTF_BIND_IP={args.bind}\nGUTF_PORT={args.port}\n')
        write_private(root / 'db.env', f'MARIADB_DATABASE=courrier\nMARIADB_USER=courrier\nMARIADB_PASSWORD={database_password}\nMARIADB_ROOT_PASSWORD={secrets.token_hex(40)}\n')
        write_private(root / 'app.env', f'''APP_NAME=COURRIER GUTF
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:{app_key}
APP_URL={args.url.rstrip('/')}
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
LOG_CHANNEL=stderr
LOG_LEVEL=warning
DB_CONNECTION=mariadb
DB_HOST=database
DB_PORT=3306
DB_DATABASE=courrier
DB_USERNAME=courrier
DB_PASSWORD={database_password}
SESSION_DRIVER=database
SESSION_COOKIE={project}-session
SESSION_LIFETIME=30
SESSION_IDLE_TIMEOUT=30
SESSION_IDLE_WARNING_SECONDS=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE={'true' if args.url.startswith('https://') else 'false'}
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_PATH=/
CACHE_STORE=database
CACHE_PREFIX={project}_
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log
MAIL_MAILER=log
MAIL_FROM_ADDRESS={args.admin_email}
MAIL_FROM_NAME=COURRIER GUTF
FILESYSTEM_DISK=local
FILESYSTEM_PUBLIC_URL=/storage
SCHEDULE_TIMEZONE=Africa/Douala
BACKUP_ARCHIVE_PASSWORD={secrets.token_urlsafe(32)}
''')
        print('Chargement des images locales. Aucun téléchargement n’est nécessaire.', flush=True)
        run(['docker', 'load', '--input', str(bundle / 'images.tar.gz')])
        for image in release['images']:
            found = run(['docker', 'image', 'inspect', '--format', '{{.Id}}', image['name']], capture_output=True, text=True).stdout.strip()
            require(found == image['id'], 'Identité image incorrecte : ' + image['name'])
        compose(root, 'config', '--quiet')
        compose(root, 'up', '-d', '--wait', '--wait-timeout', '180', 'database')
        compose(root, 'run', '--rm', '-T', '--no-deps', 'app', 'php', 'artisan', 'migrate', '--force')
        compose(root, 'run', '--rm', '-T', '--no-deps', 'app', 'php', 'artisan', 'app:agency-initialize', '--email=' + args.admin_email,
                '--name=' + args.admin_name, '--password-stdin', input=password + '\n', text=True)
        del password
        compose(root, 'up', '-d', '--wait', '--wait-timeout', '180')
        write_private(root / 'installation.json', json.dumps({'instance': args.instance, 'url': args.url, 'release': release['revision'], 'directory': str(root)}, indent=2) + '\n')
        print(f'Installation terminée : {args.url}\nAdministration locale : sudo python3 {root}/control.py status\nConserver les fichiers privés de ce dossier et les sauvegardes hors du serveur.')


def main():
    parser = argparse.ArgumentParser(description='Installer COURRIER GUTF sans modifier les autres applications.')
    parser.add_argument('--instance', required=True)
    parser.add_argument('--url', required=True)
    parser.add_argument('--port', type=int, default=8088)
    parser.add_argument('--bind', default='127.0.0.1')
    parser.add_argument('--directory')
    parser.add_argument('--admin-email', required=True)
    parser.add_argument('--admin-name', default='Super Administrateur')
    parser.add_argument('--admin-password-file', help='Provisionnement automatisé : fichier privé, jamais un argument contenant le mot de passe.')
    args = parser.parse_args()
    try:
        install(args, Path(__file__).resolve().parent)
    except (RuntimeError, ValueError, OSError, subprocess.CalledProcessError) as error:
        print('Installation interrompue : ' + str(error), file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
