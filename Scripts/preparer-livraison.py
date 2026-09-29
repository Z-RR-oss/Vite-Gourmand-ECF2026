#!/usr/bin/env python3
"""Build a deployment ZIP from a clean checkout, excluding local data and secrets."""

import hashlib
import json
import subprocess
import tempfile
import zipfile
from datetime import datetime, timezone
from pathlib import Path


ROOT = Path(__file__).resolve().parent.parent
RUNTIME_DIRECTORIES = {"Config", "Public", "Repositories", "Services", "Templates"}
RUNTIME_FILES = {"composer.json", "composer.lock", "vite_gourmand.sql.sql"}


def git(*arguments):
    return subprocess.check_output(["git", *arguments], cwd=ROOT)


def allowed(path):
    return (
        path.parts[0] in RUNTIME_DIRECTORIES
        or str(path) in RUNTIME_FILES
        or (path.parts[0] == "Scripts" and path.suffix == ".php")
    )


def verify_path(path):
    lower = path.name.lower()
    if (
        path.is_absolute()
        or ".." in path.parts
        or lower.endswith(".local.php")
        or lower == ".env"
        or lower.startswith(".env.")
        or "service-account" in lower
        or "adminsdk" in lower
        or lower.startswith("credentials")
        or lower.endswith((".pem", ".key"))
        or (path.parts[:3] == ("Public", "assets", "uploads")
            and path.name not in {".gitkeep", ".htaccess"})
    ):
        raise RuntimeError(f"Fichier privé ou inattendu refusé : {path}")
    if (ROOT / path).is_symlink() or not (ROOT / path).is_file():
        raise RuntimeError(f"Fichier absent ou lien symbolique refusé : {path}")


def build():
    if git("status", "--porcelain").strip():
        raise RuntimeError("Enregistrer les changements dans Git avant de préparer la livraison.")
    if not (ROOT / "vendor/autoload.php").is_file():
        raise RuntimeError("Exécuter composer install --no-dev avant de préparer la livraison.")
    installed = json.loads((ROOT / "vendor/composer/installed.json").read_text())
    lock = json.loads((ROOT / "composer.lock").read_text())
    expected = {p["name"]: p["version"] for p in lock["packages"]}
    actual = {p["name"]: p["version"] for p in installed["packages"]}
    if actual != expected:
        raise RuntimeError("Les dépendances installées ne correspondent pas au composer.lock de production.")

    files = [Path(p.decode()) for p in git("ls-files", "-z").split(b"\0") if p]
    files = [p for p in files if allowed(p)]
    files.extend(p.relative_to(ROOT) for p in (ROOT / "vendor").rglob("*") if p.is_file())
    files = sorted(set(files))
    for path in files:
        verify_path(path)
    commit = git("rev-parse", "HEAD").decode().strip()
    timestamp = datetime.fromtimestamp(int(git("show", "-s", "--format=%ct", "HEAD")), timezone.utc)
    zip_date = timestamp.timetuple()[:6]

    def add_file(archive, name, data):
        entry = zipfile.ZipInfo(name, date_time=zip_date)
        entry.compress_type = zipfile.ZIP_DEFLATED
        entry.create_system = 3
        entry.external_attr = 0o100644 << 16
        archive.writestr(entry, data)

    output = ROOT / "var/releases"
    output.mkdir(parents=True, exist_ok=True)
    destination = output / f"vite-gourmand-{commit[:12]}.zip"
    manifest = {
        "commit": commit,
        "document_root": "Public",
        "database_import": "vite_gourmand.sql.sql : base vide ou jetable uniquement (DROP TABLE)",
        "configuration": "Créer Config/*.local.php et installer la clé Firebase hors de Public.",
        "files": {},
    }
    with tempfile.NamedTemporaryFile(dir=output, suffix=".zip", delete=False) as temporary:
        temporary_path = Path(temporary.name)
    try:
        with zipfile.ZipFile(temporary_path, "w", zipfile.ZIP_DEFLATED) as archive:
            for path in files:
                data = (ROOT / path).read_bytes()
                manifest["files"][path.as_posix()] = hashlib.sha256(data).hexdigest()
                add_file(archive, path.as_posix(), data)
            add_file(archive, "RELEASE.json", json.dumps(manifest, ensure_ascii=False, indent=2) + "\n")
        with zipfile.ZipFile(temporary_path) as archive:
            if archive.testzip() is not None:
                raise RuntimeError("L'archive a échoué au contrôle d'intégrité.")
        temporary_path.replace(destination)
    finally:
        temporary_path.unlink(missing_ok=True)
    checksum = hashlib.sha256(destination.read_bytes()).hexdigest()
    destination.with_suffix(".sha256").write_text(f"{checksum}  {destination.name}\n")
    print(f"Livraison préparée : {destination}\n{len(files)} fichiers + manifeste ; {destination.stat().st_size} octets")


if __name__ == "__main__":
    try:
        build()
    except (OSError, ValueError, KeyError, RuntimeError, subprocess.CalledProcessError) as error:
        raise SystemExit(f"Échec de préparation : {error}")
