#!/usr/bin/env python3
"""Assemble les sources CSS dans un ordre explicite, sans dépendance de production."""

import argparse
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SOURCES = ROOT / 'Public/assets/css'
OUTPUT = ROOT / 'Public/style.css'
# L'ordre fait partie du contrat : les adaptations écrasent les règles générales.
MODULES = (
    '01-fondations.css',
    '02-formulaires.css',
    '03-navigation.css',
    '04-accueil.css',
    '05-catalogue.css',
    '06-contenus.css',
    '07-pied-de-page.css',
    '08-detail-menu.css',
    '09-contact-legal.css',
    '10-gestion.css',
    '11-responsive.css',
    '12-panneaux.css',
)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--check', action='store_true', help='Vérifier sans réécrire')
    args = parser.parse_args()
    parts = ['/* Généré par Scripts/construire-css.py. Modifier Public/assets/css/. */']
    for name in MODULES:
        parts.append(f'/* ===== {name} ===== */\n' + (SOURCES / name).read_text().strip())
    content = '\n\n'.join(parts) + '\n'
    if args.check:
        if not OUTPUT.is_file() or OUTPUT.read_text() != content:
            raise SystemExit('CSS désynchronisé : exécuter python3 Scripts/construire-css.py')
        print(f'CSS à jour : {len(MODULES)} modules assemblés dans l’ordre déclaré.')
    else:
        OUTPUT.write_text(content)
        print('Public/style.css reconstruit.')


if __name__ == '__main__':
    main()
