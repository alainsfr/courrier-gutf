"""Tester le véritable accès SSH forcé sur une agence de CI, sans réseau extérieur."""
import json
from pathlib import Path
import shutil
import socket
import subprocess
import sys
import time


def run(*args, **kwargs):
    return subprocess.run(list(args), check=True, **kwargs)


def verify(installation, bundle, workspace):
    agency_address = '10.203.252.2'
    peer_address = '10.203.252.1'
    key = workspace / 'support-test-key'
    host = workspace / 'support-test-host'
    run('ssh-keygen', '-q', '-t', 'ed25519', '-N', '', '-f', str(key))
    run('ssh-keygen', '-q', '-t', 'ed25519', '-N', '', '-f', str(host))
    private_peer = run('wg', 'genkey', capture_output=True, text=True).stdout
    peer_public = run('wg', 'pubkey', input=private_peer, capture_output=True, text=True).stdout.strip()
    del private_peer
    config = run(sys.executable, str(bundle / 'maintenance/configure-maintenance.py'), '--directory', str(installation),
                 '--ssh-public-key', str(key) + '.pub', '--peer-public-key', peer_public, '--endpoint', '127.0.0.1:51820',
                 '--address', agency_address + '/32', '--peer-address', peer_address + '/32', capture_output=True, text=True)
    report = json.JSONDecoder().raw_decode(config.stdout[config.stdout.index('{'):])[0]
    account = report['support_user']
    interface = report['interface']
    daemon = None
    try:
        run('ip', 'address', 'add', peer_address + '/32', 'dev', 'lo')
        with socket.socket() as probe:
            probe.bind(('127.0.0.1', 0))
            port = probe.getsockname()[1]
        (workspace / 'sshd').write_text(f'''Port {port}
ListenAddress {agency_address}
HostKey {host}
PidFile {workspace}/sshd.pid
PasswordAuthentication no
KbdInteractiveAuthentication no
PubkeyAuthentication yes
UsePAM yes
AllowUsers {account}
StrictModes yes
LogLevel VERBOSE
''')
        known_hosts = workspace / 'known_hosts'
        known_hosts.write_text(f'[{agency_address}]:{port} ' + Path(str(host) + '.pub').read_text())
        logfile = (workspace / 'sshd.log').open('w')
        daemon = subprocess.Popen(['/usr/sbin/sshd', '-D', '-e', '-f', str(workspace / 'sshd')], stdout=logfile, stderr=logfile)
        for _ in range(20):
            try:
                with socket.create_connection((agency_address, port), timeout=1):
                    break
            except OSError:
                time.sleep(0.2)
        ssh = ['ssh', '-b', peer_address, '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=yes', '-o',
               'UserKnownHostsFile=' + str(known_hosts), '-i', str(key), '-p', str(port), account + '@' + agency_address]
        run(*ssh, 'status')
        request = json.loads(run(*ssh, 'request', capture_output=True, text=True).stdout)
        assert request['product'] == 'courrier-gutf'
        for forbidden in ['sh', 'status; id', 'restart another-project', '/usr/lib/openssh/sftp-server']:
            denied = subprocess.run([*ssh, forbidden], capture_output=True, text=True)
            assert denied.returncode != 0 and 'root:' not in denied.stdout
        rejected = subprocess.run([*ssh, 'import'], input=b'invalid unsigned document', capture_output=True)
        assert rejected.returncode != 0
        run(*ssh, 'diagnostic')
        audit = run('journalctl', '-t', account, '--no-pager', capture_output=True, text=True).stdout
        assert 'start action=status' in audit and 'end action=request exit=0' in audit
        print('PASS: WireGuard configuration, forced SSH, allowed diagnostics, unsigned import and shell/SFTP rejection, audit', flush=True)
    finally:
        if daemon:
            daemon.terminate()
            daemon.wait(timeout=10)
        subprocess.run(['systemctl', 'disable', '--now', 'wg-quick@' + interface], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        subprocess.run(['ip', 'address', 'del', peer_address + '/32', 'dev', 'lo'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        # Only fixture resources derived from this newly installed CI account.
        if account.startswith('gutf-') and len(account) == 17:
            identity = account.removeprefix('gutf-')
            subprocess.run(['userdel', account])
            Path('/etc/sudoers.d/' + account).unlink(missing_ok=True)
            Path('/etc/wireguard/' + interface + '.conf').unlink(missing_ok=True)
            for parent in [Path('/usr/local/lib/courrier-gutf'), Path('/var/lib/courrier-gutf-support')]:
                target = parent / identity
                if target.resolve().parent == parent and target.name == identity:
                    shutil.rmtree(target, ignore_errors=True)
