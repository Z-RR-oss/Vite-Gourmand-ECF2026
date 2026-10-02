#!/usr/bin/env python3
"""Actualise la copie de remise des documents publics, sans toucher aux sources."""

import os
import re
import shutil
from pathlib import Path
from urllib.parse import unquote, urlsplit


ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "docs"
DESTINATION = ROOT / "Livrables-jury" / "Annexes"


def rewrite_links(text, source, destination):
    """Préserve les liens Markdown locaux malgré les deux niveaux ajoutés."""
    def replace(match):
        target = match.group(2)
        parsed = urlsplit(target)
        if parsed.scheme or not parsed.path or target.startswith("/"):
            return match.group(0)
        original = (source.parent / unquote(parsed.path)).resolve()
        if not original.exists():
            return match.group(0)
        try:
            resolved = DESTINATION / original.relative_to(SOURCE)
        except ValueError:
            resolved = original
        relative = Path(os.path.relpath(resolved, destination.parent)).as_posix()
        suffix = ("?" + parsed.query if parsed.query else "") + ("#" + parsed.fragment if parsed.fragment else "")
        return f"{match.group(1)}({relative}{suffix})"

    return re.sub(r"(!?\[[^\]]*\])\(([^\s)]+)\)", replace, text)


def main():
    DESTINATION.mkdir(parents=True, exist_ok=True)
    count = 0
    # Liste limitée aux documents publics : aucune configuration ni donnée var/.
    for source in sorted(SOURCE.rglob("*")):
        if source.is_symlink() or not source.is_file() or source.name.startswith("."):
            continue
        if source.suffix.lower() not in {".md", ".pdf", ".json", ".png", ".jpeg", ".jpg", ".svg"}:
            continue
        destination = DESTINATION / source.relative_to(SOURCE)
        destination.parent.mkdir(parents=True, exist_ok=True)
        if source.suffix == ".md":
            destination.write_text(rewrite_links(source.read_text(), source, destination))
        else:
            shutil.copy2(source, destination)
        count += 1
    shutil.copy2(ROOT / "vite_gourmand.sql.sql", DESTINATION / "schema-et-donnees-fictives.sql")
    (DESTINATION / "README.md").write_text("""# Documents annexes

Copie de remise actualisée depuis `docs/` par `python3 Scripts/regrouper-annexes.py`.
Modifier les sources dans `docs/`, puis relancer la commande. Les chemins écrits
dans les explications restent relatifs à la racine du projet ; les liens
Markdown ont été adaptés pour fonctionner depuis ce dossier.

- [Manuel utilisateur PDF](manuel-utilisateur.pdf) et [version texte](manuel-utilisateur.md).
- [Charte graphique PDF](charte-graphique.pdf) et [version texte](charte-graphique.md).
- [MCD PDF](../MCD/mcd-vite-gourmand.pdf), [règles du MCD](mcd.md), [schéma relationnel et UML](diagrammes.md).
- [SQL avec données fictives](schema-et-donnees-fictives.sql) : importer uniquement sur une base vide ou jetable (DROP TABLE).
- [Documentation technique](documentation-technique.md), [déploiement](deploiement.md), [Firebase](nosql.md).
- [Gestion du projet](gestion-projet.md), [conventions de code](conventions-code.md), [plan de tests](plan-tests.md).
- [Accessibilité](accessibilite.md), [rapport final](rapport-final.md), [checklist](checklist-finale.md).
- [Aide à la copie officielle](preparation-copie-ecf.md), [remise au jury](remise-jury.md), [démonstration](parcours-jury.md).
- [Mockups Figma](figma-mockups.md), [captures de recette](captures/).
- [Revue de qualité du 2 octobre](revue-qualite-20261002.md), [décisions techniques](decisions-techniques.md), [recette axe](recette-axe-20261002.json).
- [PowerPoint et script](../Presentation/README.md).

Les rapports JSON de recette sont également présents dans ce dossier. Les
documents historiques (audit initial et rapports datés) conservent leur date
et leur périmètre. Ils ne constituent pas une nouvelle recette à chaque copie.
Les identifiants privés de production et les clés d'accès sont exclus.
""")
    print(f"{count} documents copiés, avec le SQL et l'index de remise.")


if __name__ == "__main__":
    main()
