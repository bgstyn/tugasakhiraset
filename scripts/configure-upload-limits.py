#!/usr/bin/env python3
"""Prepare bounded uploads for the verified asset vhost; preserve site contents."""
import argparse
import datetime
import os
from pathlib import Path
import re
import shlex
import stat
import subprocess
import tempfile
import uuid

MARKER = '# Asset Management upload limit (managed by deploy)'


def byte_size(value):
    match = re.fullmatch(r'(\d+)\s*([KMG]?)', value.strip(), re.I)
    if not match:
        raise ValueError('Unsupported upload setting; inspect existing .user.ini before changing it')
    return int(match[1]) * (1024 ** ('', 'K', 'M', 'G').index(match[2].upper()))


def plan(site, root, hostname, ini):
    if not re.fullmatch(r'[A-Za-z0-9.-]+', hostname):
        raise ValueError('Invalid application hostname')
    names = re.findall(r'^\s*server_name\s+([^;]+);', site, re.M)
    if not any(hostname in shlex.split(nameset) for nameset in names):
        raise ValueError('Application hostname does not match the configured vhost')
    roots = list(re.finditer(r'^[ \t]*root[ \t]+([^;]+);[^\n]*', site, re.M))
    if len(roots) != 1 or shlex.split(roots[0][1]) != [root]:
        raise ValueError('Expected exactly one matching public root in the application vhost')
    sockets = set(re.findall(r'fastcgi_pass\s+unix:[^;\s]*php(\d+\.\d+)-fpm\.sock\s*;', site))
    if len(sockets) != 1:
        raise ValueError('Cannot identify one PHP-FPM socket version for this vhost')
    limits = re.findall(r'^[ \t]*client_max_body_size[ \t]+([^;]+);', site, re.M)
    managed = '\n    '+MARKER+'\n    client_max_body_size 16M;'
    if limits:
        # Refuse to rewrite an operator's independent limit or a different context.
        if len(limits) != 1 or site.count(MARKER) != 1 or not site[roots[0].end():].startswith(managed):
            raise ValueError('Existing vhost upload limit is not managed here; inspect it first')
        updated_site = site
    else:
        if MARKER in site:
            raise ValueError('Incomplete managed upload configuration')
        updated_site = site[:roots[0].end()] + managed + site[roots[0].end():]
    updated_ini = ini
    for name, minimum in (('upload_max_filesize', '5M'), ('post_max_size', '16M')):
        pattern = re.compile(r'^([ \t]*'+name+r'[ \t]*=[ \t]*)([^;\r\n]+)', re.M)
        matches = list(pattern.finditer(updated_ini))
        if matches:
            current = matches[-1]
            size = byte_size(current[2])
            if size == 0 or size >= byte_size(minimum):
                continue
            updated_ini = updated_ini[:current.start(2)] + minimum + updated_ini[current.end(2):]
        else:
            updated_ini = updated_ini.rstrip('\n')+'\n'+name+' = '+minimum+'\n'
    return updated_site, updated_ini, next(iter(sockets))


def atomic_write(path, content, original_stat=None):
    descriptor, temporary = tempfile.mkstemp(prefix='.'+path.name+'-upload-', dir=path.parent)
    try:
        with os.fdopen(descriptor, 'w', encoding='utf-8', newline='') as stream:
            stream.write(content)
            stream.flush()
            os.fsync(stream.fileno())
        os.chmod(temporary, stat.S_IMODE(original_stat.st_mode) if original_stat else 0o644)
        if original_stat:
            os.chown(temporary, original_stat.st_uid, original_stat.st_gid)
        os.replace(temporary, path)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def apply(site_path, public_root, hostname, *, nginx_root=Path('/etc/nginx')):
    site_path = site_path.resolve(strict=True)
    if not site_path.is_relative_to(nginx_root) or 'asset-management-backups' in site_path.parts:
        raise ValueError('Vhost target must remain inside /etc/nginx')
    public_root = public_root.resolve(strict=True)
    ini_path = public_root / '.user.ini'
    if ini_path.is_symlink():
        raise ValueError('Refusing to replace a linked .user.ini')
    originals = {site_path: site_path.read_text(), ini_path: ini_path.read_text() if ini_path.exists() else None}
    metadata = {path: path.stat() if path.exists() else None for path in originals}
    site, ini, series = plan(originals[site_path], str(public_root), hostname, originals[ini_path] or '')
    changes = {path: content for path, content in ((site_path, site), (ini_path, ini)) if content != originals[path]}
    if not changes:
        print('Application upload limits already configured')
        return
    backup_root = nginx_root / 'asset-management-backups'
    backup_root.mkdir(mode=0o700, exist_ok=True)
    backup = backup_root / (datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%SZ')+'-'+uuid.uuid4().hex[:8])
    backup.mkdir(mode=0o700)
    for path in changes:
        if originals[path] is not None:
            saved = backup / ('vhost.conf' if path == site_path else 'public.user.ini')
            saved.write_text(originals[path])
            saved.chmod(0o600)
    (backup/'rollback.txt').write_text('Vhost: '+str(site_path)+'\nUser ini: '+str(ini_path)+'\nUser ini existed: '+str(originals[ini_path] is not None)+'\n')
    try:
        for path, content in changes.items():
            atomic_write(path, content, metadata[path])
        subprocess.run(['nginx', '-t'], check=True)
        subprocess.run(['systemctl', 'reload', 'php'+series+'-fpm'], check=True)
    except BaseException:
        for path in changes:
            if originals[path] is None:
                path.unlink(missing_ok=True)
            else:
                atomic_write(path, originals[path], metadata[path])
        raise
    print('Application request limit 16M; PHP per-file minimum 5M; PHP POST minimum 16M')
    print('Configuration backup:', backup)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--site', type=Path, required=True)
    parser.add_argument('--public-root', type=Path, required=True)
    parser.add_argument('--hostname', required=True)
    args = parser.parse_args()
    if os.geteuid() != 0:
        parser.error('Run with sudo on the deployment server')
    apply(args.site, args.public_root, args.hostname)


if __name__ == '__main__':
    main()
