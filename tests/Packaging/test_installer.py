import argparse
import base64
import hashlib
import importlib.util
import json
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]


def module(name, relative):
    spec = importlib.util.spec_from_file_location(name, ROOT / relative)
    loaded = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(loaded)
    return loaded


installer = module('installer', 'deploy/agency/installer.py')
maintenance = module('maintenance', 'deploy/agency/maintenance/configure-maintenance.py')
remote = module('remote', 'deploy/agency/maintenance/remote.py')


class InstallerTests(unittest.TestCase):
    def test_instance_and_url_reject_shell_and_cross_application_paths(self):
        defaults = dict(instance='agence-centre', port=8088, bind='127.0.0.1', url='https://courrier.example', admin_email='admin@example.test')
        installer.validate_options(argparse.Namespace(**defaults))
        for key, value in [('instance', '../other'), ('instance', 'hello;id'), ('url', 'https://user:pass@example.test'),
                           ('url', 'https://example.test/other-app'), ('url', 'https://example.test;id'), ('port', 80), ('bind', 'example.test')]:
            with self.subTest(key=key, value=value), self.assertRaises((ValueError, RuntimeError)):
                installer.validate_options(argparse.Namespace(**{**defaults, key: value}))

    def test_package_integrity_detects_changed_missing_and_outside_files(self):
        with tempfile.TemporaryDirectory(prefix='courrier-package-test-') as directory:
            root = Path(directory)
            checksums = {}
            for name in ['compose.yaml', 'images.tar.gz', 'release.json', 'control.py', 'install-linux.sh']:
                data = b'{}' if name == 'release.json' else b'fixture'
                (root / name).write_bytes(data)
                checksums[name] = hashlib.sha256(data).hexdigest()
            (root / 'checksums.json').write_text(json.dumps(checksums))
            self.assertEqual({}, installer.verify_bundle(root))
            (root / 'control.py').write_bytes(b'changed')
            with self.assertRaisesRegex(RuntimeError, 'altéré'):
                installer.verify_bundle(root)
            checksums = {'../escape': '0' * 64}
            (root / 'checksums.json').write_text(json.dumps(checksums))
            with self.assertRaisesRegex(RuntimeError, 'Chemin'):
                installer.verify_bundle(root)

    def test_vpn_has_only_explicit_private_peer_addresses(self):
        defaults = dict(endpoint='support.example.test:51820', peer_public_key=base64.b64encode(b'a' * 32).decode(), address='10.203.0.2/32', peer_address='10.203.0.1/32')
        maintenance.validate(argparse.Namespace(**defaults))
        for key, value in [('peer_address', '0.0.0.0/0'), ('address', '10.203.0.2/24'), ('peer_address', '8.8.8.8/32'),
                           ('peer_address', '10.203.0.2/32'), ('endpoint', 'example.test;id:51820'), ('peer_public_key', 'wrong')]:
            with self.subTest(key=key), self.assertRaises((ValueError, RuntimeError)):
                maintenance.validate(argparse.Namespace(**{**defaults, key: value}))

    def test_remote_entry_point_refuses_a_shell_before_executing_anything(self):
        for command in ['sh', 'status; id', 'restart other-project', 'import && cat /etc/passwd', '']:
            with patch.object(sys, 'argv', ['remote.py', command]), patch.object(remote.subprocess, 'run') as called:
                self.assertEqual(1, remote.main())
                called.assert_not_called()


if __name__ == '__main__':
    unittest.main()
