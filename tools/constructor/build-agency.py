#!/usr/bin/env python3
"""Assemble les deux points d'entrée et les images nécessaires hors ligne."""
import argparse
import gzip
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import zipfile


def run(*args, **kwargs):
    return subprocess.run(list(args), check=True, **kwargs)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--public-key', required=True)
    parser.add_argument('--output', required=True)
    args = parser.parse_args()
    project = Path(__file__).resolve().parents[2]
    if run('git', 'status', '--porcelain', cwd=project, capture_output=True, text=True).stdout.strip():
        raise RuntimeError('Construire depuis un dépôt propre et validé pour identifier exactement la livraison.')
    output = Path(args.output).absolute()
    if output.exists():
        raise RuntimeError('La sortie existe déjà.')
    pem = Path(args.public_key).read_text(encoding='ascii')
    if 'PRIVATE KEY' in pem or not pem.startswith('-----BEGIN PUBLIC KEY-----'):
        raise RuntimeError('Clé publique seule attendue.')
    checked = run('openssl', 'pkey', '-pubin', '-in', str(Path(args.public_key).absolute()), '-text', '-noout', capture_output=True, text=True).stdout
    if 'RSA' not in checked and 'Modulus:' not in checked:
        raise RuntimeError('Clé publique RSA attendue.')
    bits = int(checked.split('(')[1].split()[0])
    if bits < 3072:
        raise RuntimeError('Clé publique RSA 3072 bits minimum requise.')
    revision = run('git', 'rev-parse', 'HEAD', cwd=project, capture_output=True, text=True).stdout.strip()
    image = 'courrier-gutf:agency-' + revision[:12]
    key_in_context = project / 'deploy/agency/issuer-public.pem'
    if key_in_context.exists():
        raise RuntimeError('Retirer la clé publique de construction précédente avant de relancer.')
    try:
        key_in_context.write_text(pem, encoding='ascii')
        run('docker', 'build', '--platform', 'linux/amd64', '-f', 'deploy/agency/Dockerfile', '-t', image, '.', cwd=project)
    finally:
        key_in_context.unlink(missing_ok=True)
    run('docker', 'pull', '--platform', 'linux/amd64', 'mariadb:11.4')
    database_id = run('docker', 'image', 'inspect', '--format', '{{.Id}}', 'mariadb:11.4', capture_output=True, text=True).stdout.strip()
    database_image = 'courrier-gutf-db:11.4-' + database_id.removeprefix('sha256:')[:12]
    run('docker', 'tag', 'mariadb:11.4', database_image)
    output.mkdir(parents=True)
    with tempfile.TemporaryDirectory(prefix='courrier-agency-package-') as workspace:
        root = Path(workspace)
        payload = root / 'courrier-gutf-agence'
        payload.mkdir()
        sources = project / 'deploy/agency'
        for filename in ['compose.yaml', 'install-linux.sh', 'installer.py', 'control.py', 'Installer-Windows.ps1']:
            shutil.copyfile(sources / filename, payload / filename)
        launcher = payload / 'Installer-Windows.ps1'
        launcher.write_bytes(b'\xef\xbb\xbf' + launcher.read_bytes().removeprefix(b'\xef\xbb\xbf'))
        shutil.copytree(sources / 'maintenance', payload / 'maintenance', ignore=shutil.ignore_patterns('__pycache__'))
        shutil.copyfile(project / 'docs/MANUEL-TECHNIQUE-INSTALLATION.md', payload / 'MANUEL-TECHNIQUE.md')
        shutil.copyfile(project / 'docs/DEMARRAGE-INSTALLATION.md', payload / 'LISEZ-MOI.md')
        release = {'revision': revision, 'architecture': 'linux/amd64', 'app_image': image, 'database_image': database_image,
                   'images': [{'name': name, 'id': run('docker', 'image', 'inspect', '--format', '{{.Id}}', name, capture_output=True, text=True).stdout.strip()} for name in [image, database_image]]}
        (payload / 'release.json').write_text(json.dumps(release, indent=2) + '\n', encoding='utf-8')
        tar_path = root / 'images.tar'
        run('docker', 'save', '--output', str(tar_path), image, database_image)
        with tar_path.open('rb') as source, gzip.open(payload / 'images.tar.gz', 'wb', compresslevel=3) as destination:
            shutil.copyfileobj(source, destination, length=1024 * 1024)
        checksums = {}
        for path in sorted(payload.rglob('*')):
            if path.is_file():
                digest = hashlib.sha256()
                with path.open('rb') as stream:
                    for block in iter(lambda: stream.read(1024 * 1024), b''):
                        digest.update(block)
                checksums[path.relative_to(payload).as_posix()] = digest.hexdigest()
        (payload / 'checksums.json').write_text(json.dumps(checksums, indent=2) + '\n', encoding='utf-8')
        archive = output / ('courrier-gutf-agence-' + revision[:12] + '-linux-windows-amd64.zip')
        with zipfile.ZipFile(archive, 'x', compression=zipfile.ZIP_STORED) as zipped:
            for path in sorted(payload.rglob('*')):
                if path.is_file():
                    zipped.write(path, path.relative_to(root).as_posix())
        digest = hashlib.sha256()
        with archive.open('rb') as stream:
            for block in iter(lambda: stream.read(1024 * 1024), b''):
                digest.update(block)
        (output / (archive.name + '.sha256')).write_text(digest.hexdigest() + '  ' + archive.name + '\n', encoding='ascii')
        print('Paquet autonome créé : ' + str(archive))


if __name__ == '__main__':
    main()
