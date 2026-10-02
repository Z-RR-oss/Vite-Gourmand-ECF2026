# Livrables pour le jury

- [Présentation orale et script](Presentation/README.md) : PowerPoint de 19 diapositives, notes et script PDF.
- [Documents annexes](Annexes/README.md) : charte, manuel, documentation technique, organisation, tests et aide à la remise.

- [MCD en PDF](MCD/mcd-vite-gourmand.pdf) : trois vues A3, avec sources SVG dans `MCD/`.
- [Mockups](Mockups/) : six captures du site, trois pages en ordinateur et mobile, et exports Figma complémentaires.
- [Figma modifiable](https://www.figma.com/design/Bk8YMNrME5HAuuW0ueAQi1) : six vues ; [périmètre et accès](Annexes/figma-mockups.md).
- [Wireframes](Wireframes/) : six schémas, chacun en SVG et PNG.
- [Charte graphique PDF](Annexes/charte-graphique.pdf) : identité et présentation des douze vues d'origine.
- [Règles et correspondance SQL du MCD](Annexes/mcd.md).

Les fichiers ont été déplacés ici depuis `docs/maquettes`, `docs/wireframes`, `docs/mcd` et `output/pdf`, à la demande de la propriétaire. Les scripts de génération utilisent désormais ce dossier. Les mockups montrent l’interface développée ; les wireframes documentent sa structure finale.

Les annexes sont une copie de remise des documents maintenus dans `docs/`. Pour les actualiser après une modification : `python3 Scripts/regrouper-annexes.py`. Les configurations et identifiants privés ne sont pas inclus. La copie officielle de l'examen et les droits d'accès aux liens restent à vérifier avant dépôt.
