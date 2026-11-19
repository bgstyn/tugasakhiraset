import importlib.util
from pathlib import Path
import unittest
import subprocess
import tempfile
import shlex
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('upload_limits', Path(__file__).resolve().parents[1] / 'configure-upload-limits.py')
limits = importlib.util.module_from_spec(spec)
spec.loader.exec_module(limits)

SITE = r'''server {
    server_name tipnpasset.duckdns.org;
    root /var/www/asset_management/public;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        include fastcgi_params;
    }
    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/tipnpasset.duckdns.org/fullchain.pem;
}
server {
    listen 80;
    server_name tipnpasset.duckdns.org;
    return 301 https://$host$request_uri;
}
'''


class UploadLimitsTest(unittest.TestCase):
    def plan(self, site=SITE, ini=''):
        return limits.plan(site, '/var/www/asset_management/public', 'tipnpasset.duckdns.org', ini)

    def test_only_one_managed_directive_is_added_and_other_vhost_bytes_are_preserved(self):
        site, ini, series = self.plan()
        self.assertEqual(series, '8.2')
        self.assertEqual(site.replace('\n    '+limits.MARKER+'\n    client_max_body_size 16M;', ''), SITE)
        self.assertEqual(site.count('client_max_body_size'), 1)
        self.assertIn('upload_max_filesize = 5M', ini)
        self.assertIn('post_max_size = 16M', ini)
        self.assertEqual(self.plan(site, ini), (site, ini, series))

    def test_existing_php_options_and_higher_limits_are_preserved(self):
        ini='; Keep operator settings\nmax_execution_time=90\nupload_max_filesize=32M\npost_max_size=64M\n'
        self.assertEqual(self.plan(ini=ini)[1], ini)
        unlimited='upload_max_filesize=0\npost_max_size=0\n'
        self.assertEqual(self.plan(ini=unlimited)[1], unlimited)

    def test_lower_php_limits_are_raised_without_removing_comments_or_other_settings(self):
        _, ini, _ = self.plan(ini='upload_max_filesize = 2M ; old size\npost_max_size = 8M\nmemory_limit=128M\n')
        self.assertIn('upload_max_filesize = 5M; old size', ini)
        self.assertIn('post_max_size = 16M', ini)
        self.assertIn('memory_limit=128M', ini)

    def test_wrong_host_root_multiple_roots_or_unknown_socket_are_rejected(self):
        for site in [SITE.replace('tipnpasset.duckdns.org', 'another.example'),
                     SITE.replace('/var/www/asset_management/public', '/other/public'),
                     SITE+'\nroot /other;\n', SITE.replace('php8.2-fpm.sock', 'custom.sock')]:
            with self.subTest(site=site), self.assertRaises(ValueError):
                self.plan(site)

    def test_an_existing_operator_limit_or_changed_managed_limit_is_not_overwritten(self):
        with self.assertRaises(ValueError):
            self.plan(SITE.replace('    root ', '    client_max_body_size 1M;\n    root ', 1))
        managed, ini, _ = self.plan()
        with self.assertRaises(ValueError):
            self.plan(managed.replace('client_max_body_size 16M;', 'client_max_body_size 64M;'), ini)

    def test_an_unknown_ini_expression_is_not_silently_replaced(self):
        with self.assertRaises(ValueError):
            self.plan(ini='upload_max_filesize=${CUSTOM_UPLOAD_LIMIT}\n')

    def test_failed_nginx_validation_restores_site_and_removes_new_ini(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary).resolve()
            nginx = root / 'nginx'
            public = root / 'public'
            nginx.mkdir()
            public.mkdir()
            site = nginx / 'asset_management'
            content = SITE.replace('/var/www/asset_management/public', shlex.quote(str(public)))
            site.write_text(content)
            failure = subprocess.CalledProcessError(1, ['nginx', '-t'])
            with patch.object(limits.os, 'chown', create=True), patch.object(limits.subprocess, 'run', side_effect=failure) as run:
                with self.assertRaises(subprocess.CalledProcessError):
                    limits.apply(site, public, 'tipnpasset.duckdns.org', nginx_root=nginx)
            self.assertEqual(site.read_text(), content)
            self.assertFalse((public / '.user.ini').exists())
            run.assert_called_once_with(['nginx', '-t'], check=True)
            backups = list((nginx / 'asset-management-backups').glob('*/vhost.conf'))
            self.assertEqual(len(backups), 1)
            self.assertEqual(backups[0].read_text(), content)

    def test_successful_apply_preserves_existing_ini_and_uses_actual_socket_on_reload(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary).resolve()
            nginx, public = root / 'nginx', root / 'public'
            nginx.mkdir()
            public.mkdir()
            site = nginx / 'asset_management'
            site.write_text(SITE.replace('/var/www/asset_management/public', shlex.quote(str(public))))
            (public / '.user.ini').write_text('max_execution_time=90\n')
            with patch.object(limits.os, 'chown', create=True), patch.object(limits.subprocess, 'run') as run:
                limits.apply(site, public, 'tipnpasset.duckdns.org', nginx_root=nginx)
                self.assertEqual(run.call_args_list[-1].args[0], ['systemctl', 'reload', 'php8.2-fpm'])
                self.assertIn('max_execution_time=90', (public / '.user.ini').read_text())
                run.reset_mock()
                limits.apply(site, public, 'tipnpasset.duckdns.org', nginx_root=nginx)
                run.assert_not_called()


if __name__ == '__main__':
    unittest.main()
