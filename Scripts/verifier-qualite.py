#!/usr/bin/env python3
"""Contrôles reproductibles, sans écriture SQL ni accès au réseau par défaut."""
import argparse
import os
from pathlib import Path
import shutil
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]


def executable(variable, name, fallback=None):
    value = os.environ.get(variable) or shutil.which(name)
    if value:
        return value
    if fallback and Path(fallback).is_file():
        return fallback
    raise SystemExit(f'{name} introuvable : renseigner {variable}.')


def run(*command):
    subprocess.run(command, cwd=ROOT, check=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--integration', action='store_true', help='Tester une base _test_ et le serveur SMTP/HTTP local déjà démarré')
    args = parser.parse_args()
    php = executable('PHP_BIN', 'php', '/Applications/XAMPP/xamppfiles/bin/php')
    node = executable('NODE_BIN', 'node')
    files = [p for directory in ['Config', 'Controllers', 'Public', 'Services', 'Repositories', 'Templates', 'Scripts', 'tests']
             for p in (ROOT / directory).rglob('*.php') if not p.name.endswith('.local.php')]
    for path in files:
        result = subprocess.run([php, '-l', str(path)], capture_output=True, text=True)
        if result.returncode:
            raise SystemExit(result.stdout + result.stderr)
    print(f'{len(files)} fichiers PHP : syntaxe valide.', flush=True)
    run(sys.executable, 'Scripts/construire-css.py', '--check')
    for path in ['Public/scripts.js', 'Public/assets/js/statistics.js', 'tests/axe-runner.js']:
        run(node, '--check', path)
    for path in list((ROOT / 'Scripts').glob('*.py')) + list((ROOT / 'tests').glob('*.py')):
        compile(path.read_text(), str(path), 'exec')
    run('git', 'diff', '--check')
    run(php, 'tests/statistics.php')
    if args.integration:
        if '_test_' not in os.environ.get('DB_NAME', ''):
            raise SystemExit('--integration exige DB_NAME avec _test_ et une base fictive réimportée.')
        # La recette HTML précède les mutations des fixtures de la suite HTTP.
        run(php, 'tests/orders.php')
        run(php, 'tests/catalogue.php')
        run(sys.executable, 'tests/accessibility_http.py')
        run(sys.executable, 'tests/quality_http.py')
        run(sys.executable, 'tests/integration_http.py')
        run(php, 'tests/images.php')
    print('Contrôles demandés réussis.', flush=True)


if __name__ == '__main__':
    try:
        main()
    except subprocess.CalledProcessError as error:
        raise SystemExit(error.returncode)
