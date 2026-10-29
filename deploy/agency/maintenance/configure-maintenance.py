#!/usr/bin/env python3
"""Configuration explicite d'une maintenance WireGuard + SSH, sans service cloud."""
import argparse
import base64
import hashlib
import ipaddress
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys


def require(value, message):
    if not value:
        raise RuntimeError(message)


def validate(args):
    require(re.fullmatch(r'[a-zA-Z0-9][a-zA-Z0-9.-]*:[0-9]{2,5}', args.endpoint), 'Endpoint attendu : nom-ou-IPv4:port.')
    require(1 <= int(args.endpoint.rsplit(':', 1)[1]) <= 65535, 'Port VPN invalide.')
    key = base64.b64decode(args.peer_public_key, validate=True)
    require(len(key) == 32, 'Clé publique WireGuard invalide.')
    local = ipaddress.IPv4Interface(args.address)
    peer = ipaddress.IPv4Interface(args.peer_address)
    require(local.network.prefixlen == peer.network.prefixlen == 32 and local.ip != peer.ip, 'Deux adresses VPN distinctes /32 sont requises.')
    require(local.ip.is_private and peer.ip.is_private, 'Choisir des adresses privées réservées à la maintenance.')
    return local, peer


def write_new(path, data, mode=0o600):
    descriptor = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, mode)
    with os.fdopen(descriptor, 'w', encoding='utf-8') as stream:
        stream.write(data)


def main():
    parser = argparse.ArgumentParser(description='Préparer une maintenance réservée au constructeur.')
    parser.add_argument('--directory', required=True)
    parser.add_argument('--ssh-public-key', required=True)
    parser.add_argument('--peer-public-key', required=True)
    parser.add_argument('--endpoint', required=True)
    parser.add_argument('--address', required=True)
    parser.add_argument('--peer-address', required=True)
    args = parser.parse_args()
    local, peer = validate(args)
    require(sys.platform.startswith('linux') and os.geteuid() == 0, 'Exécuter avec sudo dans le serveur Linux.')
    for binary in ['wg', 'wg-quick', 'systemctl', 'ssh-keygen', 'visudo', 'useradd', 'logger']:
        require(shutil.which(binary), 'Prérequis manquant : ' + binary)
    subprocess.run(['systemctl', 'is-active', '--quiet', 'ssh'], check=True)
    root = Path(args.directory).absolute()
    require(root.resolve() == root and root.stat().st_uid == 0 and not root.stat().st_mode & 0o022
            and (root / 'installation.json').is_file(), 'Installation dédiée et protégée attendue.')
    ssh_key = Path(args.ssh_public_key).read_text().strip()
    require('\n' not in ssh_key and '\r' not in ssh_key and re.fullmatch(r'(ssh-ed25519|ssh-rsa) [A-Za-z0-9+/=]+(?: [^\r\n]*)?', ssh_key), 'Clé SSH publique simple attendue, sans options.')
    result = subprocess.run(['ssh-keygen', '-lf', args.ssh_public_key], capture_output=True, text=True, check=True)
    require(ssh_key.startswith('ssh-ed25519 ') or int(result.stdout.split()[0]) >= 3072, 'Clé RSA 3072 bits minimum attendue.')
    identity = hashlib.sha256(str(root).encode()).hexdigest()[:12]
    account = 'gutf-' + identity
    interface = 'wg-g' + identity[:8]
    helper_dir = Path('/usr/local/lib/courrier-gutf') / identity
    home = Path('/var/lib/courrier-gutf-support') / identity
    wg_path = Path('/etc/wireguard') / (interface + '.conf')
    sudo_path = Path('/etc/sudoers.d') / ('gutf-' + identity)
    require(not helper_dir.exists() and not home.exists() and not wg_path.exists() and not sudo_path.exists(), 'Configuration déjà présente. Aucun remplacement automatique.')
    require(subprocess.run(['id', account], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL).returncode != 0, 'Compte système déjà présent.')
    helper_dir.mkdir(parents=True, mode=0o755)
    shutil.copyfile(Path(__file__).with_name('remote.py'), helper_dir / 'remote.py')
    os.chmod(helper_dir / 'remote.py', 0o755)
    write_new(helper_dir / 'target.json', json.dumps({'directory': str(root), 'audit_tag': account}) + '\n')
    sudo_rule = f'{account} ALL=(root) NOPASSWD: {helper_dir}/remote.py\n'
    candidate = helper_dir / 'sudo-rule'
    write_new(candidate, sudo_rule, 0o440)
    subprocess.run(['visudo', '-cf', str(candidate)], check=True)
    home.parent.mkdir(parents=True, exist_ok=True, mode=0o755)
    subprocess.run(['useradd', '--system', '--create-home', '--home-dir', str(home), '--shell', '/bin/sh', account], check=True)
    # The support account cannot edit its SSH restrictions or gain a general shell.
    os.chown(home, 0, 0)
    os.chmod(home, 0o755)
    (home / '.ssh').mkdir(mode=0o755)
    forced = f'sudo -n {helper_dir}/remote.py "$SSH_ORIGINAL_COMMAND"'
    restricted_key = f'from="{peer.ip}",restrict,command="{forced.replace(chr(34), chr(92)+chr(34))}" {ssh_key}\n'
    write_new(home / '.ssh/authorized_keys', restricted_key, 0o644)
    write_new(sudo_path, sudo_rule, 0o440)
    Path('/etc/wireguard').mkdir(mode=0o700, exist_ok=True)
    private_key = subprocess.run(['wg', 'genkey'], capture_output=True, text=True, check=True).stdout.strip()
    public_key = subprocess.run(['wg', 'pubkey'], input=private_key + '\n', capture_output=True, text=True, check=True).stdout.strip()
    write_new(wg_path, f'[Interface]\nPrivateKey = {private_key}\nAddress = {local}\n\n[Peer]\nPublicKey = {args.peer_public_key}\nEndpoint = {args.endpoint}\nAllowedIPs = {peer.ip}/32\nPersistentKeepalive = 25\n')
    del private_key
    subprocess.run(['systemctl', 'enable', '--now', 'wg-quick@' + interface], check=True)
    print(json.dumps({'support_user': account, 'agency_address': str(local.ip), 'agency_public_key': public_key,
                      'constructor_allowed_ips': str(local.ip) + '/32', 'interface': interface,
                      'ssh_example': f'ssh {account}@{local.ip} status', 'audit': f'journalctl -t {account}'}, indent=2))
    print('Ajouter cette clé publique et cette adresse /32 au serveur WireGuard constructeur. Aucun routage vers le réseau de l’agence n’est activé.')


if __name__ == '__main__':
    try:
        main()
    except (RuntimeError, ValueError, OSError, subprocess.CalledProcessError) as error:
        print('Configuration interrompue : ' + str(error), file=sys.stderr)
        sys.exit(1)
