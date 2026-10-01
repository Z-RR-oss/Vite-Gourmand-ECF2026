# Charte graphique - Vite & Gourmand

Édition du 28 septembre 2026. Export complet dans `charte-graphique.pdf` (9 pages). Source de génération : `Scripts/generer-documents.py` avec ReportLab/Pillow. Le CSS de référence est `Public/style.css`; il prévaut sur les descriptions en cas d'évolution.

## Intention

Une maison de cuisine bordelaise chaleureuse, élégante et accessible. Composition éditoriale, grands titres à empattements, illustrations originales de table et accent bordeaux. Les espaces client et gestion emploient les mêmes composants que le catalogue.

## Palette

| Token | Valeur | Usage |
| --- | --- | --- |
| primary | #672f3e | Actions, titres de rubrique, identité |
| primary-dark | #49202d | Survol des actions |
| secondary | #526b57 | Informations et repères végétaux |
| accent | #d9b788 | Décoration, pas de petit texte sur crème |
| text | #292c25 | Texte courant |
| muted | #62665b | Texte secondaire |
| background | #f7f3eb | Fond général |
| surface | #fffdf8 | Formulaires et panneaux |
| error | #922b36 | Erreurs accompagnées d'un message |

Titres : Iowan Old Style, Palatino Linotype, Book Antiqua, Georgia, serif. Corps : -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif. Polices système pour éviter une dépendance réseau. Interligne de corps 1,65, taille 16 px, titres fluides clamp().

## Logo et illustrations

Logo vectoriel exact dans `Public/assets/images/embleme.svg`, accompagné du nom visible. Maintenir ses proportions et un espace libre. La vignette de signature et les SVG menu-classique, menu-vegan, menu-fete et table-partage forment une famille cohérente. Ils ne sont pas présentés comme photographies contractuelles.

## Composants

Boutons principaux bordeaux, hauteur minimale 46 px, coins 6 px, variante secondaire. Formulaires avec labels visibles, champs larges et groupes espacés. Messages textuels pour succès/erreur. Badges avec nom du statut. Focus 3 px et décalage 4 px. Lien d'évitement, navigation mobile avec aria-expanded et fermeture Échap. Tableaux dans un conteneur défilable lorsque nécessaire; graphique accompagné d'un tableau.

## Wireframes et mockups

Trois écrans : accueil, détail du menu, contact. Pour chacun : version desktop 1 440 × 1 000 et mobile 390 × 844. Les six wireframes sont disponibles en SVG éditable et PNG dans `wireframes/`. Les six mockups PNG dans `maquettes/` sont des captures du résultat implémenté, non une invention de chiffres métier. Les wireframes documentent rétrospectivement l'organisation finale; ils ne prétendent pas avoir précédé le développement.

Le PDF réunit les douze vues. Les captures montrent la fenêtre initiale; le contenu continue au défilement, particulièrement sur mobile. Les pages principales ont été contrôlées à 390, 768 et 1 440 px (72 mesures dans `recette-responsive.json`), et contact à 320 px. Revue pragmatique sans certification RGAA exhaustive.

| Écran | Wireframe ordinateur | Wireframe mobile | Mockup ordinateur | Mockup mobile |
| --- | --- | --- | --- | --- |
| Accueil | [SVG](../Livrables-jury/Wireframes/accueil-desktop.svg) | [SVG](../Livrables-jury/Wireframes/accueil-mobile.svg) | [PNG](../Livrables-jury/Mockups/accueil-desktop.png) | [PNG](../Livrables-jury/Mockups/accueil-mobile.png) |
| Détail menu | [SVG](../Livrables-jury/Wireframes/detail-menu-desktop.svg) | [SVG](../Livrables-jury/Wireframes/detail-menu-mobile.svg) | [PNG](../Livrables-jury/Mockups/detail-menu-desktop.png) | [PNG](../Livrables-jury/Mockups/detail-menu-mobile.png) |
| Contact | [SVG](../Livrables-jury/Wireframes/contact-desktop.svg) | [SVG](../Livrables-jury/Wireframes/contact-mobile.svg) | [PNG](../Livrables-jury/Mockups/contact-desktop.png) | [PNG](../Livrables-jury/Mockups/contact-mobile.png) |
