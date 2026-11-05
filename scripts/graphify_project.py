"""Reproducible local-only code + schema index. Run from any working directory."""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parent.parent
SOURCES = ('app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'tests', 'scripts', 'docs')


def fingerprints():
    files = [p
            for folder in SOURCES for p in sorted((ROOT / folder).rglob('*'))
            if p.is_file() and p.suffix in ('.php', '.js', '.css', '.json', '.py', '.md', '.sql')]
    files += [ROOT / name for name in ('AGENTS.md', '.graphifyignore', 'requirements-graphify.txt')]
    return {p.relative_to(ROOT).as_posix(): hashlib.sha256(p.read_bytes()).hexdigest() for p in files}


def run(*args):
    subprocess.run(args, cwd=ROOT, check=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command', choices=('build', 'check'), default='build', nargs='?')
    args = parser.parse_args()
    manifest = ROOT / 'graphify-out/source-hashes.json'
    if args.command == 'check':
        previous = json.loads(manifest.read_text(encoding='utf-8')) if manifest.exists() else {}
        current = fingerprints()
        changed = sorted(k for k in previous.keys() | current.keys() if previous.get(k) != current.get(k))
        if changed:
            print('Graph is stale. Run python scripts/graphify_project.py build. Changed sources:', *changed, sep='\n')
            return 1
        print('Graph source fingerprints are current.')
        return 0
    run('php', 'scripts/graphify-schema.php')
    run('graphify', 'extract', '.', '--code-only', '--max-workers', '4', '--allow-dedup-shrink')
    run('graphify', 'cluster-only', '.', '--no-label')
    run('graphify', 'export', 'wiki')
    manifest.write_text(json.dumps(fingerprints(), indent=2) + '\n', encoding='utf-8')
    return 0


if __name__ == '__main__':
    sys.exit(main())
