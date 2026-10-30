#!/usr/bin/env python3
"""Recette réelle, sur Docker éphémère de CI uniquement."""
import argparse
import json
import importlib.util
from pathlib import Path
import secrets
import socket
import subprocess
import sys
import tempfile
import time
import urllib.request
import zipfile


def run(*args, **kwargs):
    return subprocess.run(list(args), check=True, **kwargs)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--directory', required=True)
    args = parser.parse_args()
    archive = next(Path(args.directory).glob('*.zip')).resolve()
    projects = []
    with tempfile.TemporaryDirectory(prefix='courrier-agency-smoke-') as directory:
        workspace = Path(directory)
        with zipfile.ZipFile(archive) as zipped:
            zipped.extractall(workspace)
        bundle = workspace / 'courrier-gutf-agence'
        release = json.loads((bundle / 'release.json').read_text())
        # Runtime complet sans réseau, avec les dépendances déjà présentes.
        run('docker', 'run', '--rm', '--network', 'none', '--entrypoint', 'php', release['app_image'], '-r',
            'require "vendor/autoload.php"; foreach(["pdo_mysql","gd","mbstring","zip","pcntl","intl"] as $e){if(!extension_loaded($e)){exit(1);}} if(!file_exists("public/build/manifest.json") || is_dir("tools/constructor")){exit(2);} echo "Offline runtime ready\\n";')
        password = workspace / 'initial-password'
        password.write_text('Packaging-Test-Only!' + secrets.token_hex(12))
        password.chmod(0o600)
        boot = 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); '
        try:
            for index in [1, 2]:
                name = 'ci-' + secrets.token_hex(4)
                root = workspace / ('instance-' + str(index))
                with socket.socket() as probe:
                    probe.bind(('127.0.0.1', 0))
                    port = probe.getsockname()[1]
                project = 'gutf-' + name
                projects.append((project, root))
                run(sys.executable, str(bundle / 'installer.py'), '--instance', name, '--url', f'http://127.0.0.1:{port}',
                    '--port', str(port), '--directory', str(root), '--admin-email', f'owner{index}@example.test', '--admin-password-file', str(password))
                compose = ['docker', 'compose', '--project-directory', str(root), '--env-file', str(root / '.env'), '-f', str(root / 'compose.yaml')]
                result = run(*compose, 'exec', '-T', 'app', 'php', '-r', boot + 'echo json_encode(["users"=>App\\Models\\User::count(),"email"=>App\\Models\\User::sole()->email,"verified"=>App\\Models\\User::sole()->hasVerifiedEmail(),"courriers"=>App\\Models\\Courrier::count(),"managed"=>config("distribution.managed")]);', capture_output=True, text=True)
                data = json.loads(result.stdout)
                assert data == {'users': 1, 'email': f'owner{index}@example.test', 'verified': True, 'courriers': 0, 'managed': True}, data
                response = urllib.request.urlopen(f'http://127.0.0.1:{port}/login', timeout=15)
                assert response.status == 200 and 'COURRIER' in response.read().decode()
                failed = subprocess.run([sys.executable, str(bundle / 'installer.py'), '--instance', name, '--url', f'http://127.0.0.1:{port}',
                                         '--port', str(port), '--directory', str(root), '--admin-email', 'other@example.test', '--admin-password-file', str(password)], capture_output=True, text=True)
                assert failed.returncode != 0
                run(*compose, 'exec', '-T', 'app', 'php', 'artisan', 'app:operations-heartbeat')
                time.sleep(7)
                run(*compose, 'exec', '-T', 'app', 'bash', 'deploy/run-operations.sh', 'queue', '/usr/local/bin/php')
                run(sys.executable, str(root / 'control.py'), 'status')
                request = run(sys.executable, str(root / 'control.py'), 'request', capture_output=True, text=True)
                assert json.loads(request.stdout)['product'] == 'courrier-gutf'
                run(sys.executable, str(root / 'control.py'), 'backup')
                run(*compose, 'exec', '-T', 'app', 'php', '-r', boot + '$files=Illuminate\\Support\\Facades\\Storage::disk("backups")->allFiles(); if(!count(array_filter($files,fn($f)=>str_ends_with($f,".zip")))){exit(1);}', capture_output=True, text=True)
                if index == 1:
                    spec = importlib.util.spec_from_file_location('maintenance_smoke', Path(__file__).with_name('maintenance_smoke.py'))
                    support = importlib.util.module_from_spec(spec)
                    spec.loader.exec_module(support)
                    support.verify(root, bundle, workspace)
                print('PASS: independent installation, verified owner, persistence refusal, login, workers, backup, offline dependencies', flush=True)
            roots = [root for _, root in projects]
            assert (roots[0] / 'app.env').read_text() != (roots[1] / 'app.env').read_text()
            for project, root in projects:
                containers = run('docker', 'ps', '-q', '--filter', 'label=com.docker.compose.project=' + project, capture_output=True, text=True).stdout.splitlines()
                assert len(containers) == 4
                config = json.loads(run('docker', 'compose', '--project-directory', str(root), '-f', str(root / 'compose.yaml'), 'config', '--format', 'json', capture_output=True, text=True).stdout)
                assert not config['services']['database'].get('ports')
            print('PASS: two simultaneous isolated projects; no database host port', flush=True)
        except Exception:
            for project, root in projects:
                if (root / 'compose.yaml').exists():
                    subprocess.run(['docker', 'compose', '--project-directory', str(root), '-f', str(root / 'compose.yaml'), 'logs', '--tail', '35'])
            raise
        finally:
            # CI only: delete solely the two named, generated fixture projects.
            for project, root in projects:
                if project.startswith('gutf-ci-') and root.resolve().is_relative_to(workspace.resolve()) and (root / 'compose.yaml').exists():
                    subprocess.run(['docker', 'compose', '--project-directory', str(root), '-f', str(root / 'compose.yaml'), 'down', '--volumes'])


if __name__ == '__main__':
    main()
